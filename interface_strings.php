<?PHP
/**
 * Kaltura Local Plugin for Moodle 2
 * Copyright (C) 2011 Catalyst IT (http://www.catalyst.net.nz)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local
 * @subpackage kaltura
 * @author     Brett Wilkins <brett@catalyst.net.nz>
 * @license    http://www.gnu.org/licenses/agpl.html GNU Affero GPL v3 or later
 */

function construct_interface($select, $edit, $enable_shared) {
    global $CFG, $_SESSION;

    $interfaceNodes = array();

    list($interfaceNodes['edit'], $interfaceNodes['editdata'], $editstrs) = editInterface($edit);
    list($interfaceNodes['select'], $interfaceNodes['selectdata'], $selectstrs) = selectInterface($select, $enable_shared);
    $interfaceNodes['strings'] = (object)array_merge((array)$editstrs, (array)$selectstrs);

    return $interfaceNodes;
}

function constructMediaPager($mediatype, $data) {
    $next = get_string('next', 'local_kaltura');
    $previous = get_string('previous', 'local_kaltura');
    $page = get_string('page', 'local_kaltura');

    $pagebhref = 'href="#"';
    $pagefhref = 'href="#"';
    if ($data['page']['current'] === $data['page']['count']) {
        $pagefhref = '';
    }
    if ($data['page']['current'] == 1) {
        $pagebhref = '';
    }
    $listhtml = '<div class="' . $mediatype . 'container">';
    $controlshtml =  '<div class="controls">'
                    .'<a ' . $pagebhref . ' class="pageb">' . $previous . '</a> ' . $page . ' ' . $data['page']['current'] . ' of ' . $data['page']['count'] . ' <a ' . $pagefhref . ' class="pagef">' . $next . '</a>'
                    .'</div>';

    foreach ($data['objects'] as $entry) {
        if ($mediatype == 'audio') {
            $thumbhtml = '<span><div class="kalthumb">' . $entry->name . '</div></span>';
        }
        else { //Assume video
            $thumbhtml = '<img src="' . $entry->thumbnailUrl . '" type="image/jpeg" class="kalthumb" alt="' . $entry->name . '" title="' . $entry->name . '"/>';
        }

        $listhtml .= '<span class="thumb">'
                        .'<a href="#" onclick="window.kalturaWiz.selectedEntry({entryId: \'' . $entry->id . '\', mediatype: \'' . $mediatype . '\', upload: false});return false;" class="kalturavideo" id="' . $entry->id . '">'
                            .$thumbhtml
                        .'</a>'
                    .'</span>';
    }

    $listhtml .= '</div>';

    return $controlshtml . $listhtml;
}

function editInterface($edit) {
    global $CFG, $_SESSION;

    $strs = new stdClass;
    $strs->editinfo = get_string('editinfo', 'local_kaltura');
    $strs->title = get_string('title', 'local_kaltura');
    $strs->description = get_string('description', 'local_kaltura');
    $strs->tags = get_string('tags', 'local_kaltura');
    $strs->categories = get_string('categories', 'local_kaltura');
    $strs->update = get_string('update', 'local_kaltura');
    $strs->edithelp = get_string('edithelp', 'local_kaltura');
    $strs->thumbnail = get_string('thumbnail', 'local_kaltura');

    $categories = array();
    $depth      = array();
    if (!empty($edit->categorylist)) {
        foreach ($edit->categorylist['categories']->objects as $index => $category) {
            if (empty($depth[$category->depth])) {
                $depth[$category->depth] = array();
            }
            $c              = new stdclass;
            $c->type        = 'text';
            $c->label       = $category->name;
            $c->catFullName = $category->fullName;
            $c->parentId    = $category->parentId;
            $c->catId       = $category->id;
            $depth[$category->depth][$category->id] = $c;
        }

        for ($i = count($depth)-1; $i > 0; $i--) {
            foreach ($depth[$i] as $id => $category) {
                $parent = $depth[$i-1][$category->parentId];
                if (empty($parent->children)) {
                    $parent->children = array();
                }
                $parent->children[] = $category;
            }
        }

        $categories = array_values($depth[0]);
    }

    $category_dom = '';
    if ($_SESSION['kaltura_use_shared']) {
        $category_dom = <<<CATEGORY
                    <div class="editentry">
                        <label for="editcategoriestext">$strs->categories</label>
                        <span id="editcategories">
                            <input id="editcategoriesids" type="hidden" />
                            <input id="editcategoriestext" type="text" colspan="30" disabled />
                            <div id="editcategoriestreeview">
                            </div>
                        </span>
                    </div>
CATEGORY;
    }

    $editstr = <<<EDIT
    <div id="editInterface" class="contentArea">
             <ul class="yui3-tabview-list" >
                        <li class="yui3-tab-selected"><a href="">$strs->editinfo</a></li>
                    </ul>
        <div id="edit-inner">
		<div id="edit-content">
            <input type="hidden" id="editentryid" />
            <div id="editprogressdiv">
            </div>
            <div id="editmaindiv">
                <span id="editthumbspan">
                    <img src="$CFG->wwwroot/local/kaltura/images/ajax-loader.gif" id="contribkalturathumb" alt="$strs->thumbnail" />
                </span>
                <div class="help">$strs->edithelp</div>
                <span id="editcontentspan">
                    <div class="editentry">
                        <label for="edittitle">$strs->title</label>
                        <input id="edittitle" type="text" colspan="30" />
                    </div>
                    <div class="editentry">
                        <label for="editdescription">$strs->description</label>
                        <input id="editdescription" type="text" colspan="30" />
                    </div>
                    <div class="editentry">
                        <label for="edittags">$strs->tags</label>
                        <input id="edittags" type="text" colspan="30" />
                    </div>
                    $category_dom
                </span>
            </div>
            <div id="editfooterdiv">
                <input id="editupdate" type="submit" value="$strs->update" disabled />
            </div>
			</div>
        </div>
    </div>
EDIT;

    $editdata = array('categorylist' => $categories);

    return array($editstr, $editdata, $strs);
}

function selectInterface($select, $enable_shared) {
    $show = new stdClass;

    $show->video = true; //To be configured later
    $show->videolistprivate = !empty($select->videolistprivate);
    $show->videolistpublic  = $enable_shared && !empty($select->videolistpublic);

    $show->audio = true; //To be configured later
    $show->audiolistprivate = !empty($select->audiolistprivate);
    $show->audiolistpublic  = $enable_shared && !empty($select->audiolistpublic);

    $strs = new stdClass;
    $strs->upload = get_string('upload', 'local_kaltura');
    $strs->uploadfromfile = get_string('uploadfromfile', 'local_kaltura');

    $strs->next = get_string('next', 'local_kaltura');
    $strs->previous = get_string('previous', 'local_kaltura');
    $strs->page = get_string('page', 'local_kaltura');
    $strs->close = get_string('close', 'local_kaltura');
    $strs->thumbnail = get_string('thumbnail', 'local_kaltura');


    $selectdata = array();
    $selectdata['show'] = $show;
    $dom = new stdClass;
    if ($show->video) {
        $selectdata['videourl']          = $select->videourl;
        $selectdata['videouploadurl']    = $select->videouploadurl;
        $selectdata['videolistprivate']  = $select->videolistprivate;

        $strs->video = get_string('video', 'local_kaltura');
        $strs->uploadvideo = get_string('uploadvideo', 'local_kaltura');
        $strs->uploadvideofilehelp = get_string('uploadvideofilehelp', 'local_kaltura');
        $strs->recordfromwebcam = get_string('recordfromwebcam', 'local_kaltura');
        $strs->recordwebcamhelp = get_string('recordwebcamhelp', 'local_kaltura');

        if ($show->videolistprivate) {
            $strs->myvideo = get_string('myvideo', 'local_kaltura');
            $strs->myvideohelp = get_string('myvideohelp', 'local_kaltura');

            $dom->videolistprivateli = '<li><a href="#myvideo">' . $strs->myvideo . '</a></li>';
            $dom->videolistprivate  = '<div id="myvideo" class="contentArea">';
            $dom->videolistprivate .= constructMediaPager('video', $select->videolistprivate);
            $dom->videolistprivate .= '<div class="help">' . $strs->myvideohelp . '</div>';
            $dom->videolistprivate .= '</div>';
        }
        else {
            $dom->videolistprivateli = '';
            $dom->videolistprivate = '';
        }
        if ($show->videolistpublic) {
            $selectdata['videolistpublic'] = $select->videolistpublic;

            $strs->sharedvideo = get_string('sharedvideo', 'local_kaltura');
            $strs->sharedvideohelp = get_string('sharedvideohelp', 'local_kaltura');

            $dom->videolistpublicli .= '<li><a href="#sharedvideo">' . $strs->sharedvideo . '</a></li>';
            $dom->videolistpublic  = '<div id="sharedvideo" class="contentArea">';
            $dom->videolistpublic .= constructMediaPager('video', $select->videolistpublic);
            $dom->videolistpublic .= '<div class="help">' . $strs->sharedvideohelp . '</div>';
            $dom->videolistpublic .= '</div>';
        }
        else {
            $dom->videolistpublicli = '';
            $dom->videolistpublic = '';
        }

        $dom->videoli = '<li><a href="#videotab">' . $strs->video . '</a></li>';
        $dom->video = <<<VIDEO
                <div id="videotab" class="contentArea">
                    <div id="videotabview" class="contentArea">
                        <ul>
                            <li><a href="#uploadvideotab">$strs->uploadfromfile</a></li>
                            <li><a href="#webcamtab">$strs->recordfromwebcam</a></li>
                            $dom->videolistprivateli
                            $dom->videolistpublicli
                        </ul>
                        <div class="contentArea">
                            <div id="uploadvideotab" class="contentArea">
                                <label for="uploadvideospan">$strs->uploadvideo</label>
                                <span id="uploadvideospan">
                                    <input type="submit" id="uploadvideobutton" value="$strs->upload" />
                                </span>
                                <div class="help">$strs->uploadvideofilehelp</div>
                            </div>
                            <div id="webcamtab" class="contentArea">
                                <div class="flashTarget"></div>
                                <div class="help">$strs->recordwebcamhelp</div>
                            </div>
                            $dom->videolistprivate
                            $dom->videolistpublic
                        </div>
                    </div>
                </div>
VIDEO;
    }
    else {
        $dom->videoli = '';
        $dom->video = '';
    } //END if show video

    if ($show->audio) {
        $selectdata['audiourl']          = $select->audiourl;
        $selectdata['audiouploadurl']    = $select->audiouploadurl;
        $selectdata['audiolistprivate']  = $select->audiolistprivate;

        $strs->audio = get_string('audio', 'local_kaltura');
        $strs->uploadaudio = get_string('uploadaudio', 'local_kaltura');
        $strs->uploadaudiofilehelp = get_string('uploadaudiofilehelp', 'local_kaltura');
        $strs->recordfrommicrophone = get_string('recordfrommicrophone', 'local_kaltura');
        $strs->recordmicrophonehelp = get_string('recordmicrophonehelp', 'local_kaltura');

        if ($show->audiolistprivate) {
            $strs->myaudio           = get_string('myaudio', 'local_kaltura');
            $strs->myaudiohelp       = get_string('myaudiohelp', 'local_kaltura');

            $dom->audiolistprivateli = '<li><a href="#myaudio">' . $strs->myaudio . '</a></li>';
            $dom->audiolistprivate   = '<div id="myaudio" class="contentArea">';
            $dom->audiolistprivate  .= constructMediaPager('audio', $select->audiolistprivate);
            $dom->audiolistprivate  .= '<div class="help">' . $strs->myaudiohelp . '</div>';
            $dom->audiolistprivate  .= '</div>';
        }
        else {
            $dom->audiolistprivateli = '';
            $dom->audiolistprivate   = '';
        }
        if ($show->audiolistpublic) {
            $selectdata['audiolistpublic'] = $select->audiolistpublic;
            $strs->sharedaudio       = get_string('sharedaudio', 'local_kaltura');
            $strs->sharedaudiohelp   = get_string('sharedaudiohelp', 'local_kaltura');

            $dom->audiolistpublicli .= '<li><a href="#sharedaudio">' . $strs->sharedaudio . '</a></li>';
            $dom->audiolistpublic    = '<div id="sharedaudio" class="contentArea">';
            $dom->audiolistpublic   .= constructMediaPager('audio', $select->audiolistpublic);
            $dom->audiolistpublic   .= '<div class="help">' . $strs->sharedaudiohelp . '</div>';
            $dom->audiolistpublic   .= '</div>';
        }
        else {
            $dom->audiolistpublicli  = '';
            $dom->audiolistpublic    = '';
        }

        $dom->audioli = '<li><a href="#audiotab">' . $strs->audio . '</a></li>';
        $dom->audio = <<<AUDIO
                <div id="audiotab" class="contentArea">
                    <div id="audiotabview" class="contentArea">
                        <ul>
                            <li><a href="#uploadaudiotab">$strs->uploadfromfile</a></li>
                            <li><a href="#mictab">$strs->recordfrommicrophone</a></li>
                            $dom->audiolistprivateli
                            $dom->audiolistpublicli
                        </ul>
                        <div class="contentArea">
                            <div id="uploadaudiotab" class="contentArea">
                                <label for="uploadaudiospan">$strs->uploadaudio</label>
                                <span id="uploadaudiospan">
                                    <input type="submit" id="uploadaudiobutton" value="$strs->upload" />
                                </span>
                                <div class="help">$strs->uploadaudiofilehelp</div>
                            </div>
                            <div id="mictab" class="contentArea">
                                <div class="flashTarget"></div>
                                <div class="help">$strs->recordmicrophonehelp</div>
                            </div>
                            $dom->audiolistprivate
                            $dom->audiolistpublic
                        </div>
                    </div>
                </div>
AUDIO;
    }
    else {
        $dom->audioli = '';
        $dom->audio = '';
    } //END if show audio

    $select = <<<SELECT
    <div id="selectionInterface" class="contentArea">
        <ul>
            $dom->videoli
            $dom->audioli
        </ul>
        <div>
            $dom->video
            $dom->audio
        </div>
    </div>
SELECT;

    return array($select, $selectdata, $strs);
}
?>
