<?php
error_reporting(E_ALL ^ E_NOTICE);

$to_branch = $argv[1];
$from_branch = $argv[2];
$out_dir = $argv[3];

// 检测输出目录
if($out_dir != "" && !file_exists($out_dir)) {
    die("output directory is not exist!");
}
//生成一个默认目录
if($out_dir == "") {
    $index = 0;
    while(file_exists("temp" . $index)) {
        $index++;
    }
    $out_dir = "./temp" . $index;
}

exec ("git rev-parse --show-toplevel", $output);
$project_root_dir = $output[0];

$outputfile = [];
exec("git diff $to_branch $from_branch --name-only",$outputfile);

exec("git branch",$branch);
$branch_name = null;
for($i=0;$i<count($branch);$i++) {
    $b_arr = explode(" ", $branch[$i]);
    if($b_arr[0] == "*") {
        $branch_name = trim($b_arr[1]);
        break;
    }
}

if($branch_name == null || $branch_name == "") {
    die("can't find current branch");
}

exec("git checkout $to_branch 2>&1");

$branch = [];
exec("git branch",$branch);
for($i=0;$i<count($branch);$i++) {
    $b_arr = explode(" ", $branch[$i]);
    if($b_arr[0] == "*") {
        $b_name = "";
        for($j=1;$j<count($b_arr);$j++) {
            $b_name .= $b_arr[$j];
        }

        if(!stripos($b_name, $to_branch)) {
            die("can't checkout to " . $b_name);
        }
        break;
    }
}

// 复制文件到目录
for($i=0;$i<count($outputfile);$i++) {
    if(stripos($outputfile[$i], "gitignore")) {
        continue;
    }

    $source_file = $project_root_dir . "/" . $outputfile[$i];
    if(file_exists($source_file)) {
        $out_file = $out_dir . "/" . $outputfile[$i];
        if(xcopy($source_file, $out_file)){
            echo $source_file . "\n";
        }
    }
}

exec("git checkout $branch_name 2>&1");

function xcopy($source_file, $out_file) {
    $out_file = str_replace('\\', "/", $out_file);
    $o_arr = explode("/", $out_file);
    $out_filename = $o_arr[count($o_arr) - 1];
    $out_dir = "";
    for($i=0;$i<count($o_arr)-1;$i++) {
        $out_dir .= $o_arr[$i] . "/";
    }

    if(!file_exists($out_dir)) {
        if(!mkdir($out_dir, 0755, true)) {
            return false;
        }
    }

    if(file_exists($out_file)) {
        unlink($out_file);
    }

    return copy($source_file, $out_file);
}