<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = mysqli_connect('localhost', 'variolan', 'variolan', 'variolan');

/* Create table, import data. First time.
  $db->query("CREATE TABLE IF NOT EXISTS `dataset` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(64) NOT NULL,
  `firstname` varchar(64) NOT NULL,
  `lastname` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `gender` varchar(16) NOT NULL,
  `birthDate` date NOT NULL,
  PRIMARY KEY (`id`)
  )"
  );
 */
/*
  ini_set('max_execution_time', 12000);

  $infile = 'dataset_for_import.txt'; //https://drive.google.com/file/d/1Dwb1alDAQCAPwz7Eg306BVbWtGdfkUCy/view?usp=sharing
  $f = fopen($infile, 'r');
  $i = 0;
  $a = fgetcsv($f, 1024, ','); //first row
  while($a = fgetcsv($f, 1024, ',')) {
  $db->query("INSERT INTO `dataset` (`category`,`firstname`,`lastname`,`email`,`gender`,`birthDate`) VALUES ('{$a[0]}','{$a[1]}','{$a[2]}','{$a[3]}','{$a[4]}','{$a[5]}')");
  $i++;
  if ($i % 50 == 0) {
  echo $i . ', '; //prevent to closing browser connection
  }
  }
 */

define('ON_PAGE', 10);

if (isset($_GET['onpage'])) {
    $onpage = (int) $_GET['onpage'];
} else {
    $onpage = ON_PAGE;
}
if (isset($_GET['page'])) {
    $page = (int) $_GET['page'];
} else {
    $page = 1;
}
if (isset($_GET['category'])) {
    $category_selected = (int) $_GET['category'];
} else {
    $category_selected = 0;
}
if (isset($_GET['age'])) {
    $age = (int) $_GET['age'];
} else {
    $age = 0;
}
if (isset($_GET['ageFrom'])) {
    $ageFrom = (int) $_GET['ageFrom'];
} else {
    $ageFrom = 0;
}
if (isset($_GET['ageTo'])) {
    $ageTo = (int) $_GET['ageTo'];
} else {
    $ageTo = 0;
}
if (isset($_GET['gender'])) {
    $gender_selected = (int) $_GET['gender'];
} else {
    $gender_selected = 0;
}
if (isset($_GET['birth'])) {
    $birth = filter_var($_GET['birth'], FILTER_SANITIZE_STRING);
} else {
    $birth = '';
}

$q = $db->query("select distinct category from dataset");
$categories = array();
while ($r = $q->fetch_assoc()) {
    $categories[] = $r['category'];
}
array_unshift($categories, 'Select category');
$q = $db->query("select distinct gender from dataset");
$genders = array();
while ($r = $q->fetch_assoc()) {
    $genders[] = $r['gender'];
}
array_unshift($genders, 'Select gender');

$sql = '';
if ($category_selected > 0) {
    $sql .= " and `category`='" . $categories[$category_selected] . "'";
}
if ($gender_selected > 0) {
    $sql .= " and `gender`='" . $genders[$gender_selected] . "'";
}
if (!empty($birth)) {
    $sql .= " and `birthDate`='" . $birth . "'";
}
if ($age > 0) {
    $startDate = date('Y-m-d', strtotime("first day of " . ($age + 1) . " years ago", time()));
    $endtDate = date('Y-m-d', strtotime("last day of $age years ago", time()));
    $sql .= " and (`birthDate` >= '$startDate' and `birthDate` <= '$endtDate')";
}
if ($ageFrom > 0) {
    $fromDate = date('Y-m-d', strtotime("first day of " . ($ageFrom + 1) . " years ago", time()));
    $sql .= " and `birthDate` <= '$fromDate'";
}
if ($ageTo > 0) {
    $toDate = date('Y-m-d', strtotime("last day of " . $ageTo . " years ago", time()));
    $sql .= " and `birthDate` >= '$toDate'";
}

$q = $db->query("select count(*) as cnt from dataset where 1" . $sql);
$r = $q->fetch_assoc();
$totalrows = (int) $r['cnt'];
$pages = ceil($totalrows / $onpage);

$sql = "select * from `dataset` where 1" . $sql;

if (isset($_POST['exportAction']) && $_POST['exportAction'] === 'run') {
    $datasets = $db->query($sql);
    if ($datasets && $datasets->num_rows > 0) {
        $fname = uniqid();
        $f = fopen($fname, 'w');
        foreach ($datasets as $dataset) {
            fputcsv($f, array_values($dataset), ',');
        }
        fclose($f);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . 'rexit_' . date('m-d_H-i') . '.csv"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fname));
        flush();
        readfile($fname);
        unlink($fname);
    }
}

$sql .= " limit " . (($page - 1) * $onpage) . ", " . $onpage;
$datasets = $db->query($sql);
?><!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>RexIT тестовое задание</title>
        <meta name="Description" content="https://docs.google.com/document/d/1pyLGfuZ_MTUZvGVuDrTe2Zj7pEz_axBSF8B-Bk68DEY/edit" />
        <style>
            .perc24 { width:24%; display: inline-block; }
            .perc49 { width:49%; display: inline-block; }
            .perc74 { width:74%; display: inline-block; }
            body { font: 14pt sans-serif; }
        </style>
    </head>
    <body>
        <div id="container">
            <h5><?php echo $sql; ?></h5>
            <div id="filters_top">
                <form name="mainTable" action="" method="get">
                    <div class="perc74">
                        <select name="category" onchange="document.forms['mainTable'].submit();">
                            <?php foreach ($categories as $k => $category) { ?>
                                <option value="<?php echo $k; ?>"<?php echo ($k == $category_selected) ? ' selected' : ''; ?>> <?php echo $category; ?></option>
                            <?php } ?>
                        </select>
                        <select name="gender" onchange="document.forms['mainTable'].submit();">
                            <?php foreach ($genders as $k => $gender) { ?>
                                <option value="<?php echo $k; ?>"<?php echo ($k == $gender_selected) ? ' selected' : ''; ?>> <?php echo $gender; ?></option>
                            <?php } ?>
                        </select>
                        <input name="birth" type="date" value="<?php echo $birth; ?>" placeholder="Birthday" />
                        <input name="age" type="number" style="max-width: 48px;" value="<?php echo ($age) ? $age : ''; ?>" placeholder="Age" />
                        <input name="ageFrom" type="number" style="max-width: 48px;" value="<?php echo ($ageFrom) ? $ageFrom : ''; ?>" placeholder="From Age" />&mdash;<input name="ageTo" type="number" style="max-width: 48px;" value="<?php echo ($ageTo) ? $ageTo : ''; ?>" placeholder="To Age" />
                        <input type="submit" value=" Apply " />
                        <span style="text-align: center; padding-left: 20px;">
                            <button type="button" onclick="document.forms['exportForm'].submit();" />Export</button>
                        </span>
                    </div>
                    <div class="perc24">
                        Page <?php echo $page; ?> of <?php echo $pages; ?>
                        <select name="page" onchange="document.forms['mainTable'].submit();">
                            <?php for ($i = 1; $i <= $pages; $i++) { ?>
                                <option value="<?php echo $i; ?>"<?php echo ($i == $page) ? ' selected' : ''; ?>> <?php echo $i; ?></option>
                            <?php } ?>
                        </select>
                        &nbsp;
                        <select name="onpage" onchange="document.forms['mainTable'].submit();">
                            <option value="<?php echo ON_PAGE; ?>"<?php echo (ON_PAGE == $onpage) ? ' selected' : ''; ?>> <?php echo ON_PAGE; ?>
                            <option value="<?php echo (ON_PAGE * 3); ?>"<?php echo ((ON_PAGE * 3) == $onpage) ? ' selected' : ''; ?>> <?php echo (ON_PAGE * 3); ?>
                            <option value="<?php echo (ON_PAGE * 5); ?>"<?php echo ((ON_PAGE * 5) == $onpage) ? ' selected' : ''; ?>> <?php echo (ON_PAGE * 5); ?>
                        </select>
                    </div>
                </form>
                <form name="exportForm" id="exportForm" action="" method="post">
                    <input type="hidden" name="exportAction" value="run" />
                </form>
            </div>
            <?php if ($datasets && $datasets->num_rows > 0): ?>
                <div id="data_main">
                    <table style="width: 100%;">
                        <thead>
                        <th><span>id</span></th>
                        <th><span>category</span></th>
                        <th><span>firstname</span></th>
                        <th><span>lastname</span></th>
                        <th><span>email</span></th>
                        <th><span>gender</span></th>
                        <th><span>birthDate</span></th>
                        <th><span></span></th>
                        </thead>
                        <?php while ($dataset = $datasets->fetch_assoc()) : ?>
                            <tr>
                                <td><?php echo $dataset['id']; ?></td>
                                <td><?php echo $dataset['category']; ?></td>
                                <td><?php echo $dataset['firstname']; ?></td>
                                <td><?php echo $dataset['lastname']; ?></td>
                                <td><?php echo $dataset['email']; ?></td>
                                <td><?php echo $dataset['gender']; ?></td>
                                <td><?php echo $dataset['birthDate']; ?></td>
                                <td></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <div id="error-msg">
                    <h3 style="text-align: center;">Not found</h3>
                </div>
            <?php endif; ?>
        </div> <!-- container -->
    </body>
