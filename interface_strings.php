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

function construct_interface($select, $edit) {
    global $CFG;

    $strs = new stdClass;
    $strs->close = get_string('close', 'local_kaltura');
    $strs->thumbnail = get_string('thumbnail', 'local_kaltura');
    $strs->editinfo = get_string('editinfo', 'local_kaltura');
    $strs->title = get_string('title', 'local_kaltura');
    $strs->description = get_string('description', 'local_kaltura');
    $strs->tags = get_string('tags', 'local_kaltura');
    $strs->categories = get_string('categories', 'local_kaltura');
    $strs->update = get_string('update', 'local_kaltura');
    $strs->upload = get_string('upload', 'local_kaltura');
    $strs->uploadvideo = get_string('uploadvideo', 'local_kaltura');
    $strs->uploadaudio = get_string('uploadaudio', 'local_kaltura');
    $strs->video = get_string('video', 'local_kaltura');
    $strs->audio = get_string('audio', 'local_kaltura');
    $strs->uploadfromfile = get_string('uploadfromfile', 'local_kaltura');
    $strs->recordfrommicrophone = get_string('recordfrommicrophone', 'local_kaltura');
    $strs->recordfromwebcam = get_string('recordfromwebcam', 'local_kaltura');
    $strs->myaudio = get_string('myaudio', 'local_kaltura');
    $strs->myvideo = get_string('myvideo', 'local_kaltura');
    $strs->sharedaudio = get_string('sharedaudio', 'local_kaltura');
    $strs->sharedvideo = get_string('sharedvideo', 'local_kaltura');

    $interfaceNodes = array();

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

    $editstr[] = <<<EDIT
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
                    <div class="editentry">
                        <label for="editcategoriestext">$strs->categories</label>
                        <span id="editcategories">
                            <input id="editcategoriesids" type="hidden" />
                            <input id="editcategoriestext" type="text" colspan="30" disabled />
                            <div id="editcategoriestreeview">
                            </div>
                        </span>
                    </div>
                </span>
            </div>
            <div id="editfooterdiv">
                <input id="editupdate" type="submit" value="$strs->update" disabled />
            </div>
			</div>
        </div>
    </div>

EDIT;

    $interfaceNodes['edit'] = implode('', $editstr);
    $interfaceNodes['editdata'] = array(
        'categorylist'      => $categories,
    );

    $interfaceNodes['select'] = <<<SELECT
    <div id="selectionInterface" class="contentArea">
        <ul>
            <li><a href="#videotab">$strs->video</a></li>
            <li><a href="#audiotab">$strs->audio</a></li>
        </ul>
        <div>
            <div id="videotab" class="contentArea">
                <div id="videotabview" class="contentArea">
                    <ul>
                        <li><a href="#uploadvideotab">$strs->uploadfromfile</a></li>
                        <li><a href="#webcamtab">$strs->recordfromwebcam</a></li>
                        <li><a href="#myvideo">$strs->myvideo</a></li>
                        <li><a href="#sharedvideo">$strs->sharedvideo</a></li>
                    </ul>
                    <div class="contentArea">
                        <div id="uploadvideotab" class="contentArea">
                            <label for="uploadvideospan">$strs->uploadvideo</label>
                            <span id="uploadvideospan">
                                <input type="submit" id="uploadvideobutton" value="$strs->upload" />
                            </span>
                        </div>
                        <div id="webcamtab" class="contentArea">
                        </div>
                        <div id="myvideo" class="contentArea">
SELECT;
    $interfaceNodes['select'] .= constructMediaPager('video', $select->videolistprivate);
    $interfaceNodes['select'] .= <<<SELECT
                        </div>
                        <div id="sharedvideo" class="contentArea">
SELECT;
    $interfaceNodes['select'] .= constructMediaPager('video', $select->videolistpublic);
    $interfaceNodes['select'] .= <<<SELECT
                        </div>
                    </div>
                </div>
            </div>
            <div id="audiotab" class="contentArea">
                <div id="audiotabview" class="contentArea">
                    <ul>
                        <li><a href="#uploadaudiotab">$strs->uploadfromfile</a></li>
                        <li><a href="#mictab">$strs->recordfrommicrophone</a></li>
                        <li><a href="#myaudio">$strs->myaudio</a></li>
                        <li><a href="#sharedaudio">$strs->sharedaudio</a></li>
                    </ul>
                    <div class="contentArea">
                        <div id="uploadaudiotab" class="contentArea">
                            <label for="uploadaudiospan">$strs->uploadaudio</label>
                            <span id="uploadaudiospan">
                                <input type="submit" id="uploadaudiobutton" value="$strs->upload" />
                            </span>
                        </div>
                        <div id="mictab" class="contentArea">
                        </div>
                        <div id="myaudio" class="contentArea">
SELECT;
    $interfaceNodes['select'] .= constructMediaPager('audio', $select->audiolistprivate);
    $interfaceNodes['select'] .= <<<SELECT
                        </div>
                        <div id="sharedaudio" class="contentArea">
SELECT;
    $interfaceNodes['select'] .= constructMediaPager('audio', $select->audiolistpublic);
    $interfaceNodes['select'] .= <<<SELECT
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
SELECT;

    $interfaceNodes['selectdata'] = array(
        'videourl'          => $select->videourl,
        'audiourl'          => $select->audiourl,
        'videouploadurl'    => $select->videouploadurl,
        'audiouploadurl'    => $select->audiouploadurl,
        'audiolistpublic'   => $select->audiolistpublic,
        'videolistpublic'   => $select->videolistpublic,
        'audiolistprivate'  => $select->audiolistprivate,
        'videolistprivate'  => $select->videolistprivate,
    );

    return $interfaceNodes;
}

function constructMediaPager($mediatype, $data) {
    $pagebdisabled = '';
    $pagefdisabled = '';
    if ($data['page']['current'] === $data['page']['count']) {
        $pagefdisabled = ' disabled="disabled" ';
    }
    if ($data['page']['current'] == 1) {
        $pagebdisabled = ' disabled="disabled" ';
    }
    $listhtml = '<div class="' . $mediatype . 'container">';
    $controlshtml =  '<div class="controls">'
                    .'<a href="#" class="pageb"' . $pagebdisabled . '>&lt;</a> Page ' . $data['page']['current'] . ' <a href="#" class="pagef"' . $pagefdisabled . '>&gt;</a>'
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

    return $listhtml . $controlshtml;
}
?>
