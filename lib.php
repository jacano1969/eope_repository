<?php

/**
 * Moodle repository plugin for http://e-ope.ee/repositoorium
 * @author Mart Mangus
 * @license GPL
 */

class repository_eope_repository extends repository {

    const apiurl = 'http://www.e-ope.ee/_download/euni_repository/api/';
    private $listing = array(
        'nologin' => true,
        'dynload' => true
    );

    public function __construct($repositoryid, $context = SITEID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
    }

    function get_file($url, $filename) {
        global $CFG;
        if (substr($filename, -4) != '.html')
            $filename .= '.html';
        $path = $this->prepare_file($filename);
        $redirectcontent = file_get_contents($CFG->wwwroot . '/repository/eope_repository/repository-redirect.html');
        $redirectcontent = str_replace('{{URL}}', $url, $redirectcontent);
        file_put_contents($path, $redirectcontent);
        return array('path'=>$path, 'url'=>$url);
    }

    /**
     * @arg $path
     * Possible paths:
     *   all_entries/<school_id>/<entry_id>
     *   my_entries/<entry_id>
     *   search/<search_string>/<entry_id>
     */
    public function get_listing($path='', $page='') {

        //global $USER;
        //$USER->email;

        $this->listing['path'] = array(
            array('name' => get_string('repository', 'repository_eope_repository'),'path' => '')
        );

        if ($path == '') {
            $this->listing_start();
        } else {
            $paths = explode('/', $path);
            switch ($paths[0]) {
                case 'all_entries':
                    $this->listing_all($paths);
                    break;
                case 'my_entries':
                    $this->listing_my($paths);
                    break;              
                case 'search':
                    $this->listing_search($paths);
                    break;
                default:
                    throw new Exception("Error: Nothing available with this path: '$path'");
            }
        }
        return $this->get_current_listing();
    }

    private function listing_start() {
        $itemslist = array(
            array('title' => get_string('my_entries', 'repository_eope_repository'),
                'path' => 'my_entries',
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217',
                'children' => array()),
            array('title' => get_string('all_entries', 'repository_eope_repository'),
                'path' => 'all_entries',
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217',
                'children' => array())
        );
        $this->listing['list'] = $itemslist;
    }

    /**
     * @param $paths
     *   [1] -- school ID
     *   [2] -- entry ID
     */
    private function listing_all($paths) {
        $composedlist = array();
        $depth = count($paths);
        switch ($depth) {
            case 1:
                $encoded = file_get_contents(self::apiurl . 'list-schools');
                $schools = json_decode($encoded, true);
                $composedlist = $this->list_schools($schools);
                break;
            case 2:
                $encoded = file_get_contents(self::apiurl . 'school-entries?school_id=' . intval($paths[1]));
                $entries = json_decode($encoded, true);
                $composedlist = $this->list_entries($entries, 'all_entries/' . $paths[1] . '/');
                break;
            case 3:
                $composedlist = $this->list_files($paths[2]);
                break;
            default:
                throw new Exception('Error: This depth level is not defined: ' . $depth);
        }

        // Building path
        $this->listing['path'] []= 
            array('name' => get_string('all_entries', 'repository_eope_repository'), 'path' => 'all_entries');
        if ($depth > 1) {
            $this->listing['path'] []= 
                array('name' => 'School Name', 'path' => 'all_entries/' . $paths[1]);
            if ($depth > 2)
                $this->listing['path'] []= 
                    array('name' => 'Entry Name', 'path' => 'all_entries/' . $paths[1] . '/' . $paths[2]);
        }
        $this->listing['list'] = $composedlist;
    }
    private function listing_my($paths) {
        //TODO
        switch (count($paths)) {
            case 1:
                $itemslist = array(
                    array('title' => 'Minu sissekanne 1',
                        'path' => 'my_entries/entry_id=123',
                        'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217#TODO-FIX-THIS',
                        'children' => array()),
                    array('title' => 'Minu sissekanne 2',
                        'path' => 'my_entries/entry_id=124',
                        'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217#TODO-FIX-THIS',
                        'children' => array())
                );
                break;
            case 2:
                $itemslist = array(
                    array('title'=>'Minu fail 1.zip .html',
                        'thumbnail'=>'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=icon&rev=217&component=repository_eope_repository#TODO-FIX-THIS',
                        'source'=>'http://e-ope.ee/_download/euni_repository/file/821/kameerika.zip'),
                    array('title'=>'Minu fail 2.zip .html',
                        'thumbnail'=>'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=icon&rev=217&component=repository_eope_repository#TODO-FIX-THIS',
                        'source'=>'http://e-ope.ee/_download/euni_repository/file/821/kameerika.zip')
                );
                break;
            default:
                throw new Exception('Error: Such path is not supported');
        }
        $this->listing['list'] = $itemslist;
    }

    /**
     * @param $paths
     *   [1] -- search string
     *   [2] -- entry ID
     */
    private function listing_search($paths) {

        $composedlist = array();
        $depth = count($paths);
        switch ($depth)
        {
            case 2:
                $encoded = file_get_contents(self::apiurl . 'search?text=' . $paths[1]);
                $entries = json_decode($encoded, true);
                $composedlist = $this->list_entries($entries, 'search/' . $paths[1] . '/');
                break;

            case 3:
                $composedlist = $this->list_files($paths[2]);
                break;

            default:
                throw new Exception('Error: This depth level (2) is not defined: ' . $depth);
        }
    }

    private function get_current_listing() {
        return $this->listing;
    }

    private function list_entries($entries, $path)
    {
        foreach ($entries as $id => $entry) {
            $composedlist[] = array(
                'title' => $entry['title'], //TODO: list authors
                'path' => $path . $id,
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217',
                'children' => array()
            );
        }
        return $composedlist;
    }

    private function list_schools($schools)
    {
        foreach ($schools as $id => $schoolname) {
            $composedlist[] = array(
                'title' => $schoolname,
                'path' => 'all_entries/' . intval($id),
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Ffolder-32&rev=217',
                'children' => array()
            );
        }
        return $composedlist;
    }

    private function list_files($entryid)
    {
        $encoded = file_get_contents(self::apiurl . 'entry-files?entry_id=' . intval($entryid));
        $files = json_decode($encoded, true);
        foreach ($files as $file) {
            $composedlist[] = array(
                'title' => $file['file_name'] . ' (' . $this->format_filesize($file['file_size']) . ') .html',
                'source' => $file['url'],
                'thumbnail' => 'https://h1.moodle.e-ope.ee/theme/image.php?theme=anomaly&image=f%2Funknown-32&rev=217'
            );
        }
        return $composedlist;
    }

    public function search($text) {
        $this->listing_search(array('search', '$text'));
        return $this->get_current_listing();
    }

    private function format_filesize($bytes, $precision = 2) { 
        $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 

    // will be called when installing a new plugin in admin panel
    /*
    public static function plugin_init() {
        $result = true;
        // do nothing
        return $result;
    }
    */
}
