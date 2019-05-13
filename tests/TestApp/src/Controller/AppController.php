<?php
namespace App\Controller;

use Origin\Controller\Controller;
use Origin\I18n\I18n;

class AppController extends Controller
{
    /**
     * This is called immediately after construct, so you don't have
     * to overload it. Load and configure components, helpers etc.
     */
    public function initialize()
    {
        parent::initialize();
        
        $this->loadComponent('Flash');

        $this->loadHelper('Html');
        $this->loadHelper('Form');
        $this->loadHelper('Flash');
        $this->loadHelper('Number');
        $this->loadHelper('Date');

        /*
         * Start I18n. This will autodetect locale and settings if you do not pass array
         * with settings. e.g ['locale' => 'en_GB','language'=>'en','timezone'=>'Europe/London']
         */
        I18n::initialize();
    }

    /**
     * This is called before the controller action is executed.
     */
    public function startup()
    {
    }

    /**
     * This is called after the controller action is executed.
     */
    public function shutdown()
    {
    }
}