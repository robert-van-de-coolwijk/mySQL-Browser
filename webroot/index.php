<?php


include_once("../core/class.init.php");

Init::StartPage();

class Index{
    public static function getMenu(){
        $pagesArr = array();
        $dh  = opendir('../pages/');

        while (false !== ($filename = readdir($dh))) {
            if(Tools::startsWith($filename, 'page.')){
                $pageObj = new stdClass();

                $pageObj->fileName = $filename;
                $pageObj->name = Index::getPageNameFromFileName($filename);

                $pagesArr[] = $pageObj;
            }
        }

        return $pagesArr;
    }

    public static function getPageNameFromFileName($fFileName){
        $temp = str_replace(array('page.', '.php', '_'), array('', '', ' '), $fFileName);
        $pageName = ucwords($temp);

        return $pageName;
    }

    public static function getMenuHTML($fPages){
        $output = '';

        foreach($fPages as $pageObj){
            $output .= sprintf('<a href="../pages/%s" target="main_frame">%s</a>', $pageObj->fileName, $pageObj->name);
        }

        return $output;
    }
}

$menuArr = Index::getMenu();

$menuOut = Index::getMenuHTML($menuArr);

?>
        
<div id="main_menu">
    <?php echo $menuOut; ?>
</div>
<div id="main_frame">
    <iframe name="main_frame" style="border: 0; margin: 2px 0 0 2px; width: 99%; height: 99%;">

    </iframe>
</div>


<?php
Init::EndPage();
?>