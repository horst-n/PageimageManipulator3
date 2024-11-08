<?php namespace ProcessWire;




class PageimageManipulator3 extends WireData implements Module {        // ConfigurableModule

    static public function getModuleInfo()
    {
        return [
            'title'      => 'Pageimage Manipulator 3',
            'version'    => '0.4.3',
            'summary'    => 'This module provides basic API Imagemanipulations for the naming scheme beginning with PW 2.6+!',
            'author'     => 'horst',
            'href'       => '',
            'singular'   => true,
            'autoload'   => true,
            'requires'   => 'ProcessWire>=3.0.165, PHP>=7.2.0'
        ];
    }


    protected $optionNames = [
        'autoRotation',
        'upscaling',
        'cropping',
        'quality',
        'sharpening',
        'bgcolor',
        'targetFilename',
        'outputFormat',
        'thumbnailColorizeCustom',
        'thumbnailCoordsPermanent'
    ];


    /**
     * Populate default settings
     */
    public function __construct()
    {
    }


    /**
     * Initialize the module and setup hooks
     */
    public function init()
    {
        $this->addHook('Pageimage::pim3Load', $this, 'getPageimageManipulator3');
    }


    /**
     * Return a ready-to-use copy of the ImageManipulator02 for Pageimages
     */
    public function getPageimageManipulator3($event)
    {
        $pageimage = $event->object;
        $p = pathinfo($pageimage->filename);
        $prefix = null;
        $options = null;
        $override = false;
        $outputFormat = null;
        $targetFilename = null;

        if (count($event->arguments)===1 && is_string($event->arguments[0]))
        {
            // we only have the prefix
            $prefix = $event->arguments[0];
        }
        else if (count($event->arguments)===2 && is_string($event->arguments[0]))
        {
            // we have a prefix and a second param
            $prefix = $event->arguments[0];
            if (is_bool($event->arguments[1]))
            {
                $override = $event->arguments[1];
            }
            if (is_array($event->arguments[1]))
            {
                $options = array();
                foreach($event->arguments[1] as $k=>$v)
                {
                    if (in_array($k, $this->optionNames))
                    {
                        if ('outputFormat'==$k) $outputFormat = $v;
                        if ('targetFilename'==$k) $targetFilename = $v;
                        $options["$k"] = $v;
                    }
                }
            }
        }
        else if (count($event->arguments)===3 && is_string($event->arguments[0]))
        {
            // we have the prefix and two other params
            $prefix = $event->arguments[0];
            // we let the user pass the arguments in any order he want, so we have to check which is which
            $bool = is_bool($event->arguments[1]) ? 1 : null;
            $bool = is_bool($event->arguments[2]) ? 2 : $bool;
            $array = is_array($event->arguments[1]) ? 1 : null;
            $array = is_array($event->arguments[2]) ? 2 : $array;
            // stick it to the
            $override = null===$bool ? false : $event->arguments[$bool];
            if (null!==$array)
            {
                $options = array();
                foreach($event->arguments[$array] as $k=>$v)
                {
                    if (in_array($k, $this->optionNames))
                    {
                        if ('outputFormat'==$k) $outputFormat = $v;
                        if ('targetFilename'==$k) $targetFilename = $v;
                        $options["$k"] = $v;
                    }
                }
            }
        }

        if (empty($prefix))
        {
            $prefix = substr(md5($pageimage->filename),0,8);
        }
        $prefix = wire('sanitizer')->pageName(trim(trim($prefix),'_'));
        // for new naming scheme since PW 2.5.11
        $suffix = str_replace('-', '', $prefix);
        $suffix = "-pim2-{$suffix}";
        if (false === strpos($p['filename'], '.')) $suffix = '.' . $suffix;

        if (is_null($outputFormat) || !in_array(mb_strtolower($outputFormat), array('gif','png','jpg')))
        {
            $outputFormat = $p['extension'];
        }
        $outputFormat = mb_strtolower($outputFormat);
        if (empty($targetFilename))
        {
            #$targetFilename = $p['dirname'] .'/pim_'. $prefix .'_'. $p['filename'] .'.'. $outputFormat;
            $targetFilename = $p['dirname'] . "/" . $p['filename'] . $suffix . "." . $outputFormat;
        }

        // check if the imagefile already exists
        if (true!==$override)
        {
            $override = file_exists($targetFilename) && is_readable($targetFilename) ? false : true;
        }

        $options = is_array($options) ? $options : array();
        $options['targetFilename'] = $targetFilename;
        $options['outputFormat'] = $outputFormat;

        // load the ImageManipulator3 with the Pageimage
        require_once(wire('config')->paths->PageimageManipulator3 . 'ImageManipulator3.class.php');
        $pim = new ImageManipulator3($pageimage, $options, !$override);
        $event->return = $pim;
    }



    /**
     * Return a ready-to-use (empty) copy of the ImageManipulator02 or one to operate with an imagefile
     */
    public function imLoad($filename = null, $options = null)
    {
        // load the ImageManipulator3 with an imagefile or empty
        require_once(wire('config')->paths->PageimageManipulator3 . 'ImageManipulator3.class.php');
        $fim = new ImageManipulator3($filename, $options, false);
        return $fim;
    }

}


