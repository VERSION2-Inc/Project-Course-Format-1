<?php
/**
 * Delete a section and its module instances
 * 
 * $Id: deletesection.php 329 2009-07-03 09:18:29Z malu $
 */

    require_once '../../../config.php';
    require_once './lib.php';
    
    
    $section_id = required_param('id', PARAM_INT);
    
    $section = get_record('course_sections', 'id', $section_id);
    if (!$section) {
        error('Section not found');
    }
    $project_title = get_record('course_project_title', 'sectionid', $section->id);
    if (!$project_title) {
        error('Section directory name was missing');
    }
    
    require_login($section->course);
    require_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $section->course));
    
    
    if (optional_param('cancel')) {
        
        /**** キャンセル ****/
        
        redirect("$CFG->wwwroot/course/view.php?id=$section->course#section-$section->section");
        
    } else if (optional_param('confirm') && data_submitted() && confirm_sesskey()) {
        
        /**** セクション削除を実行 ****/
        
        // セクション内のモジュールインスタンスを削除
        $mods = get_records_sql("
            SELECT cm.id AS cmid, cm.instance, m.name
            FROM {$CFG->prefix}course_modules cm
               , {$CFG->prefix}modules        m
            WHERE cm.module  = m.id
              AND cm.section = $section->id
        ");
        if ($mods) {
            $mod_delete_functions = array();
            foreach ($mods as $mod) {
                $modname = clean_param($mod->name, PARAM_SAFEDIR);
                
                if (empty($mod_delete_functions[$modname])) {
                    $lib = "$CFG->dirroot/mod/$modname/lib.php";
                    include_once $lib;
                    
                    $func = $modname.'_delete_instance';
                    if (!is_callable($func)) {
                        error("This module is missing important code! ($lib)");
                    }
                    $mod_delete_functions[$modname] = $func;
                }
                
                if (!$mod_delete_functions[$modname]($mod->instance)) {
                    notify("Could not delete the $modname (instance)");
                }
                if (!delete_course_module($mod->cmid)) {
                    notify("Could not delete the $modname (coursemodule)");
                }
                
                // セクションを削除するのでセクションシーケンスの更新は不要
                
                add_to_log($section->course, 'course', 'delete mod',
                           "format/project/deletesection.php?id=$section->id",
                           "$mod->name $mod->instance", $mod->cmid);
            }
        }
        
        if (!delete_records('course_sections', 'id', $section->id)) {
            notify("Could not delete section");
        }
        
        // 削除したセクション１つぶん位置を詰める
        $sql = "
            UPDATE {$CFG->prefix}course_sections
            SET section = section - 1
            WHERE course = $section->course AND section > $section->section
        ";
        if (!execute_sql($sql, FALSE)) {
            notify("Could not justify sections");
        }
        
        // コースのセクション数を１つ減らす
        $sql = "
            UPDATE {$CFG->prefix}course
            SET numsections = numsections - 1
            WHERE id = $section->course
        ";
        if (!execute_sql($sql, FALSE)) {
            notify("Could not decrement number of course sections");
        }
        
        rebuild_course_cache($section->course);
        
        if (optional_param('delete_dir')) {
            // セクションディレクトリを削除
            if (file_exists($shared_lib = $CFG->dirroot.'/blocks/sharing_cart/shared/SharingCart_FileSystem.php')) {
                require_once $shared_lib;
            } else {
                require_once dirname(__FILE__).'/shared/SharingCart_FileSystem.php';
            }
            SharingCart_FileSystem::remove(
                "$CFG->dataroot/$section->course/$project_title->directoryname",
                SharingCart_FileSystem::RECURSIVE
            );
            
            add_to_log($section->course, 'course', 'delete section directory',
                       "format/project/deletesection.php?id=$section->id",
                       "$section->section");
        }
        
        add_to_log($section->course, 'course', 'delete section',
                   "format/project/deletesection.php?id=$section->id",
                   "$section->section");
        
        redirect("$CFG->wwwroot/course/view.php?id=$section->course#section-$section->section");
        
    } else {
        
        /**** セクション削除の確認 ****/
        
        $course = get_record('course', 'id', $section->course);
        
        $str_title = get_string('sectiondelete', 'format_project');
        
        print_header_simple($str_title, '', build_navigation(
            array(
                array('name' => $str_title, 'link' => null, 'type' => 'title')
            )
        ));
        
        print_simple_box_start('center');
        echo '
        <div style="text-align:center; margin:auto;">';
        {
            echo '
            <form id="theform" method="post" action="deletesection.php">
            <div style="display:none;">
                <input type="hidden" name="id" value="'.$section->id.'" />
                <input type="hidden" name="sesskey" value="'.sesskey().'" />
            </div>
            <div>'.get_string('sectiondeleteconfirm', 'format_project').'</div>
            <table border="0" style="margin:1em auto;">
            <tr>
                <th style="text-align:left;">'.
                    htmlspecialchars($section->summary ? $section->summary : "# $section->section").
                    ' ['.get_string('directoryname', 'format_project').': '.
                        htmlspecialchars($project_title->directoryname).']</th>
            </tr>';
            
            if ($section->sequence) {
                $modinfo = &get_fast_modinfo($course);
                
                $opt_noclean          = new stdClass;
                $opt_noclean->noclean = true;
                
                foreach (explode(',', $section->sequence) as $cmid) {
                    if (empty($modinfo->cms[$cmid]))
                        continue;
                    
                    $mod = $modinfo->cms[$cmid];
                    
                    if ($mod->modname == 'label') {
                        $html = '<span class="label">'
                              . format_text(empty($mod->extra) ? '' : $mod->extra,
                                            FORMAT_HTML, $opt_noclean)
                              . '</span>';
                    } else {
                        $icon = empty($mod->icon)
                              ? "$CFG->modpixpath/$mod->modname/icon.gif"
                              : "$CFG->pixpath/".$mod->icon;
                        $html = '<img alt="" src="'.$icon.'" class="activityicon" />'
                              . '<span>'
                              . format_string($mod->name, true, $course->id)
                              . '</span>';
                    }
                    echo '
            <tr>
                <td style="text-align:left; vertical-align:top;">'.$html.'</td>
            </tr>';
                }
            }
            
            echo '
            <tr>
                <td style="text-align:left;">
                    <fieldset style="border:1px solid;">
                        <legend>'.get_string('options', 'format_project').'</legend>
                        <dl>
                            <dt><label>
                                <input type="checkbox" name="delete_dir" />
                                '.get_string('sectiondeletedir', 'format_project').'
                            </label></dt>
                            <dd id="delete_dir_warning">
                                '.nl2br(get_string('sectiondeletedirwarning', 'format_project')).'
                            </dd>
                        </dl>
                    </fieldset>
                </td>
            </tr>
            </table>
            <div>
                <input type="submit" name="confirm" value="'.get_string('yes').'" />
                <input type="submit" name="cancel" value="'.get_string('no').'" />
            </div>
            </form>';
        }
        echo '
        </div>';
        print_simple_box_end();
        
        print_footer($course);
    }

?>