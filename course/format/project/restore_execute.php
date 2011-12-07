<?php //$Id: restore_execute.html,2008/4/1 12:00:00 Akio Ohnishi  Exp $
    //This page receives the required info and executes the restore
    //with the parameters suplied. Whe finished, delete temporary
    //data from backup_tables and temp directory

    //Get objects from session
    if ($SESSION) {
        $info = $SESSION->info;
        $course_header = $SESSION->course_header;
        $restore = $SESSION->restore;
    }

    //Add info->original_wwwroot to $restore to be able to use it in all the restore process
    //(mainly when decoding internal links)
    $restore->original_wwwroot = $info->original_wwwroot;
    //Add info->backup_version to $restore to be able to detect versions in the restore process
    //(to decide when to convert wiki texts to markdown...)
    $restore->backup_version = $info->backup_backup_version;

    //Check login
    require_login();

    //Check admin
    if (!empty($id)) {
        if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $id))) {
            if (empty($to)) {
                error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
            } else {
                if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_COURSE, $to)) 
                    && !has_capability('moodle/site:import',  get_context_instance(CONTEXT_COURSE, $to))) {
                    error("You need to be a teacher or admin user to use this page.", "$CFG->wwwroot/login/index.php");
                }
            }
        }
    } else {
        if (!has_capability('moodle/site:restore', get_context_instance(CONTEXT_SYSTEM, SITEID))) {
            error("You need to be an admin user to use this page.", "$CFG->wwwroot/login/index.php");
        }
    }

    //Check site
    if (!$site = get_site()) {
        error("Site not found!");
    }
    $errorstr = '';

    $status = project_restore_execute($restore,$info,$course_header,$errorstr);
    
    if (!$status) {
        error ("An error has occurred and the restore could not be completed!");
    }

    if (empty($restore->importing)) {
        //Print final message
        print_simple_box(get_string("restorefinished"),"center");
    } else {
        print_simple_box(get_string("importdatafinished"),"center");
        
        $file = $CFG->dataroot . '/' 
            . $SESSION->import_preferences->backup_course 
            . '/backupdata/' . $SESSION->import_preferences->backup_name;
        if (is_readable($file)) {
            unlink($file);
        }
        else {
            error_log("import course data: couldn't unlink $file");
        }
        unset($SESSION->restore);
    }
    
// SharingCart_Restore が自動で行うので省略
//    // セクションのリンクを変更
//    if (! $sectionobject = get_course_section($section, $course->id)) {
//        error("Section data was incorrect (can't find it)");
//    }
//    project_format_rename_section_links(&$course, $sectionobject->id, $restore->newdirectoryname, false, true);
    
//    // コースキャッシュの再構築
//    rebuild_course_cache($course->id, true);
    
    print_continue("$CFG->wwwroot/course/view.php?id=".$restore->course_id);

?>
