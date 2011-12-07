<?php // $Id: format.php 329 2009-07-03 09:18:29Z malu $
      // Display the whole course as "topics" made of of modules
      // In fact, this is very similar to the "weeks" format, in that
      // each "topic" is actually a week.  The main difference is that
      // the dates aren't printed - it's just an aesthetic thing for
      // courses that aren't so rigidly defined by time.
      // Included from "view.php"
    
    require_once(dirname(__FILE__) . '/v2uploader/v2uploader.php');
    require_once(dirname(__FILE__) . '/lib.php');     // for project course format.
    require_once(dirname(__FILE__) . '/converter.php');
    
    $topic = optional_param('topic', -1, PARAM_INT);
    
    // Bounds for block widths
    // more flexible for theme designers taken from theme config.php
    $lmin = (empty($THEME->block_l_min_width)) ? 100 : $THEME->block_l_min_width;
    $lmax = (empty($THEME->block_l_max_width)) ? 210 : $THEME->block_l_max_width;
    $rmin = (empty($THEME->block_r_min_width)) ? 100 : $THEME->block_r_min_width;
    $rmax = (empty($THEME->block_r_max_width)) ? 210 : $THEME->block_r_max_width;

    define('BLOCK_L_MIN_WIDTH', $lmin);
    define('BLOCK_L_MAX_WIDTH', $lmax);
    define('BLOCK_R_MIN_WIDTH', $rmin);
    define('BLOCK_R_MAX_WIDTH', $rmax);

    $preferred_width_left  = bounded_number(BLOCK_L_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]),  
                                            BLOCK_L_MAX_WIDTH);
    $preferred_width_right = bounded_number(BLOCK_R_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]), 
                                            BLOCK_R_MAX_WIDTH);
    if ($topic != -1) {
        $displaysection = course_set_display($course->id, $topic);
    } else {
        if (isset($USER->display[$course->id])) {       // for admins, mostly
            $displaysection = $USER->display[$course->id];
        } else {
            $displaysection = course_set_display($course->id, 0);
        }
    }
    
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if (($marker >=0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
        $course->marker = $marker;
        if (! set_field("course", "marker", $marker, "id", $course->id)) {
            error("Could not mark that topic for this course");
        }
    }

    $streditsummary   = get_string('editsummary');
    $stradd           = get_string('add');
    $stractivities    = get_string('activities');
    $strshowalltopics = get_string('showalltopics');
    $strtopic         = get_string('topic');
    $strgroups        = get_string('groups');
    $strgroupmy       = get_string('groupmy');
    $editing          = $PAGE->user_is_editing();

    if ($editing) {
        $strstudents = moodle_strtolower($course->students);
        $strtopichide = get_string('topichide', '', $strstudents);
        $strtopicshow = get_string('topicshow', '', $strstudents);
        $strmarkthistopic = get_string('markthistopic');
        $strmarkedthistopic = get_string('markedthistopic');
        $strmoveup = get_string('moveup');
        $strmovedown = get_string('movedown');
        $strdelete = get_string('delete');
    }

    // AJAXコース編集機能によってセクション削除ボタンが消されるのを防止する
    require_js($CFG->wwwroot.'/course/format/project/ajax_override.js');

/// Layout the whole page as three big columns.
    echo '<table id="layout-table" cellspacing="0" summary="'.get_string('layouttable').'"><tr>';

/// The left column ...
    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':

    if (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
        echo '<td style="width:'.$preferred_width_left.'px" id="left-column">';
        if (!empty($THEME->customcorners)) {
            echo '<div class="bt"><div></div></div>';
            echo '<div class="i1"><div class="i2"><div class="i3">';
        }
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        if (!empty($THEME->customcorners)) {
            echo '</div></div></div>';
            echo '<div class="bb"><div></div></div>';
        }
        echo '</td>';
    }

            break;
            case 'middle':
/// Start main column
    echo '<td id="middle-column">';
    if (!empty($THEME->customcorners)) {
        echo '<div class="bt"><div></div></div>';
        echo '<div class="i1"><div class="i2"><div class="i3">';
    }
    echo '<a name="startofcontent"></a>';

    print_heading_block(get_string('projectoutline', 'format_project'), 'outline');

    echo '<table class="topics" width="100%" summary="'.get_string('layouttable').'">';

/// If currently moving a file then show the current clipboard
    if (ismoving($course->id)) {
        $stractivityclipboard = strip_tags(get_string('activityclipboard', '', addslashes($USER->activitycopyname)));
        $strcancel= get_string('cancel');
        echo '<tr class="clipboard">';
        echo '<td colspan="3">';
        echo $stractivityclipboard.'&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey='.$USER->sesskey.'">'.$strcancel.'</a>)';
        echo '</td>';
        echo '</tr>';
    }

/// Print Section 0

    $section = 0;
    $thissection = $sections[$section];

    if ($thissection->summary or $thissection->sequence or isediting($course->id)) {
        echo '<tr id="section-0" class="section main">';
        echo '<td class="left side">&nbsp;</td>';
        echo '<td class="content">';
        
        echo '<div class="summary">';
        $summaryformatoptions->noclean = true;
        echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);

        if (isediting($course->id)) {
            echo '<a title="'.$streditsummary.'" '.
                 ' href="format/project/editsection.php?id='.$thissection->id.'"><img src="'.$CFG->pixpath.'/t/edit.gif" '.
                 ' alt="'.$streditsummary.'" /></a><br /><br />';
        }
        echo '</div>';

        print_section($course, $thissection, $mods, $modnamesused);

        if (isediting($course->id)) {
            print_section_add_menus($course, $section, $modnames);
        }

        echo '</td>';
        echo '<td class="right side">&nbsp;</td>';
        echo '</tr>';
        echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';
    }


/// Now all the normal modules by topic
/// Everything below uses "section" terminology - each "section" is a topic.

    $timenow = time();
    $section = 1;
    $sectionmenu = array();

    while ($section <= $course->numsections) {
        
        if (!empty($sections[$section])) {
            $thissection = $sections[$section];

        } else {
            unset($thissection);
            $thissection->course = $course->id;   // Create a new section structure
            $thissection->section = $section;
            $thissection->summary = '';
            $thissection->visible = 1;
            if (!$thissection->id = insert_record('course_sections', $thissection)) {
                notify('Error inserting new topic!');
            }
        }
        
        // セクション表示状態
        $showsection = (has_capability('moodle/course:viewhiddensections', $context) or $thissection->visible or !$course->hiddensections);

        if ($showsection) {

            $currenttopic = ($course->marker == $section);

            $currenttext = '';
            if (!$thissection->visible) {
                $sectionstyle = ' hidden';
            } else if ($currenttopic) {
                $sectionstyle = ' current';
                $currenttext = get_accesshide(get_string('currenttopic','access'));
            } else {
                $sectionstyle = '';
            }

            echo '<tr id="section-'.$section.'" class="section main'.$sectionstyle.'">';
            echo '<td class="left side">'.$currenttext.$section.'</td>';
            
            echo '<td class="content">';
            if (!has_capability('moodle/course:viewhiddensections', $context) and !$thissection->visible) {   // Hidden for students
                echo get_string('notavailable');
            } else {
                // セクションタイトルを取得
                $sectiontitle = project_format_get_title($course, $thissection->id, $section, $mods);
                
                if (!empty($displaysection) and $displaysection != $section) {
                    if ($showsection) {
                        $strtitle = strip_tags(format_string($sectiontitle->directoryname,true));
                        $sectionmenu['topic='.$section] = s($section.' - '.$strtitle);
                    }
                    $section++;
                    continue;
                }

                echo '<div class="summary">';
                $summaryformatoptions->noclean = true;
                echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);

                if (isediting($course->id)) {
                    echo ' <a title="'.$streditsummary.'" href="format/project/editsection.php?id='.$thissection->id.'">'.
                         '<img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.$streditsummary.'" /></a>';
                }
                
                // プロジェクトフォーマットコントローラー
                if (isediting($course->id)) {
                    // 編集中
                    echo '&nbsp;&nbsp;&nbsp;['.get_string('directoryname','format_project').': '.$sectiontitle->directoryname.'] ';
                    echo ' <a title="'.get_string('edittitle','format_project').'" href="format/project/edittitle.php?id='.$sectiontitle->id.'">'.
                         '<img src="'.$CFG->pixpath.'/t/edit.gif" alt="'.get_string('edittitle','format_project').'" /></a>';
                    $button = helpbutton('format/project/directoryname', get_string('directoryname','format_project'), 'moodle', true, false, '', true);
                    echo $button;  

                    echo '&nbsp;&nbsp;';

	                //    echo '<br />';  
                    
                    // アップローダーの表示
                    // リソースアップローダー
                    //echo '<div class="container">';
                    
                        //echo '<div class="resource_upload">';
                        	// FlashUploader
		                    echo '<span class="project_cursor" title="'.get_string('resourceuploadtitle', 'format_project').'">';
		                    v2uploader_put_plugin('ruswf', $course->id, $thissection->id, true, $thissection->id);
		                    echo '</span>';
                            echo '<span id="ruprgs'.$thissection->id.'"></span>';
                            echo '&nbsp;';
                            
                            //$button = helpbutton('format/project/resourceupload', get_string('resourceupload','format_project'), 'moodle', true, false, '', true);
                            //echo $button;
                        //echo '</div>';

                        // コースアップローダー
                        //echo '<div class="coursefile_upload">';
                        	// FlashUploader
		                    echo '<span class="project_cursor" title="'.get_string('coursefileuploadtitle', 'format_project').'">';
		                    v2uploader_put_plugin('cfuswf', $course->id, $thissection->id, false, $thissection->id);
		                    echo '</span>';
                            echo '<span id="cfuprgs'.$thissection->id.'"></span>';
                            
                            $button = helpbutton('format/project/fileupload', get_string('fileupload','format_project'), 'moodle', true, false, '', true);
                            echo $button;
                            
                            echo '&nbsp;&nbsp;';
                            
                        //echo '</div>';

                        // バックアップ・リストア
                        //echo '<div class="project_backup">';
                            echo '<a title="'.get_string('backupsectiontitle', 'format_project').'" href="format/project/backup.php?id='.$course->id.'&amp;section='.$section.'">';
                            echo '<img src="'.$CFG->pixpath.'/i/backup.gif" class="icon" alt="'.get_string('backup').'" />';
                            echo '</a>';

                            echo '<a title="'.get_string('importsectiontitle', 'format_project').'" href="format/project/import.php?id='.$course->id.'&amp;section='.$section.'">';
                            echo '<img src="'.$CFG->pixpath.'/i/restore.gif" class="icon" alt="'.get_string('restore').'" />';
                            echo '</a>';

                            $button = helpbutton('format/project/sectionbackuprestore', get_string('sectionbackuprestore','format_project'), 'moodle', true, false, '', true);
                            echo $button;
                        //echo '</div>';

                        //echo '<div class="project_clearer"></div>';
                        
                    //echo '</div>';
                }
                
                echo '</div>';
                
                
                print_section($course, $thissection, $mods, $modnamesused);
                
                if (isediting($course->id)) {
                    print_section_add_menus($course, $section, $modnames);
                }
            }
            echo '</td>';

            echo '<td class="right side">';
            if ($displaysection == $section) {      // Show the zoom boxes
                echo '<a href="view.php?id='.$course->id.'&amp;topic=0#section-'.$section.'" title="'.$strshowalltopics.'">'.
                     '<img src="'.$CFG->pixpath.'/i/all.gif" alt="'.$strshowalltopics.'" /></a><br />';
            } else {
                $strshowonlytopic = get_string('showonlytopic', '', $section);
                echo '<a href="view.php?id='.$course->id.'&amp;topic='.$section.'" title="'.$strshowonlytopic.'">'.
                     '<img src="'.$CFG->pixpath.'/i/one.gif" alt="'.$strshowonlytopic.'" /></a><br />';
            }

            if (isediting($course->id)) {
                if ($course->marker == $section) {  // Show the "light globe" on/off
                    echo '<a href="view.php?id='.$course->id.'&amp;marker=0&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strmarkedthistopic.'">'.
                         '<img src="'.$CFG->pixpath.'/i/marked.gif" alt="'.$strmarkedthistopic.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;marker='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strmarkthistopic.'">'.
                         '<img src="'.$CFG->pixpath.'/i/marker.gif" alt="'.$strmarkthistopic.'" /></a><br />';
                }

                if ($thissection->visible) {        // Show the hide/show eye
                    echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strtopichide.'">'.
                         '<img src="'.$CFG->pixpath.'/i/hide.gif" alt="'.$strtopichide.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strtopicshow.'">'.
                         '<img src="'.$CFG->pixpath.'/i/show.gif" alt="'.$strtopicshow.'" /></a><br />';
                }

                if ($section > 1) {                       // Add a arrow to move section up
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=-1&amp;sesskey='.$USER->sesskey.'#section-'.($section-1).'" title="'.$strmoveup.'">'.
                         '<img src="'.$CFG->pixpath.'/t/up.gif" alt="'.$strmoveup.'" /></a><br />';
                }

                if ($section < $course->numsections) {    // Add a arrow to move section down
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=1&amp;sesskey='.$USER->sesskey.'#section-'.($section+1).'" title="'.$strmovedown.'">'.
                         '<img src="'.$CFG->pixpath.'/t/down.gif" alt="'.$strmovedown.'" /></a><br />';
                }

                { // セクション削除
                    echo '<a href="format/project/deletesection.php?id='.$thissection->id.'" title="'.$strdelete.'">'.
                         '<img src="'.$CFG->pixpath.'/t/delete.gif" alt="'.$strdelete.'" /></a><br />';
                }
            }

            echo '</td></tr>';
            echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';
        }

        $section++;
    }
    echo '</table>';

    if (!empty($sectionmenu)) {
        echo '<div align="center" class="jumpmenu">';
        echo popup_form($CFG->wwwroot.'/course/view.php?id='.$course->id.'&amp;', $sectionmenu,
                   'sectionmenu', '', get_string('jumpto'), '', '', true);
        echo '</div>';
    }

    if (!empty($THEME->customcorners)) {
        echo '</div></div></div>';
        echo '<div class="bb"><div></div></div>';
    }
    echo '</td>';

            break;
            case 'right':
    // The right column
    if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing) {
        echo '<td style="width:'.$preferred_width_right.'px" id="right-column">';
        if (!empty($THEME->customcorners)) {
            echo '<div class="bt"><div></div></div>';
            echo '<div class="i1"><div class="i2"><div class="i3">';
        }
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        if (!empty($THEME->customcorners)) {
            echo '</div></div></div>';
            echo '<div class="bb"><div></div></div>';
        }
        echo '</td>';
    }

            break;
        }
    }
    echo '</tr></table>';
    
?>
