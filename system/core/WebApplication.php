<?php

class WebApplication extends Application
{

    /**
     * @var mixed the application-wide layout. Defaults to 'main' (relative to {@link getLayoutPath layoutPath}).
     * If this is false, then no layout will be used.
     */
    public $layout = 'main';

    /**
     * @var array mapping from controller ID to controller configurations.
     * Each name-value pair specifies the configuration for a single controller.
     * A controller configuration can be either a string or an array.
     * If the former, the string should be the class name or
     * {@link YiiBase::getPathOfAlias class path alias} of the controller.
     * If the latter, the array must contain a 'class' element which specifies
     * the controller's class name or {@link YiiBase::getPathOfAlias class path alias}.
     * The rest name-value pairs in the array are used to initialize
     * the corresponding controller properties. For example,
     * <pre>
     * array(
     *   'post'=>array(
     *      'class'=>'path.to.PostController',
     *      'pageTitle'=>'something new',
     *   ),
     *   'user'=>'path.to.UserController',,
     * )
     * </pre>
     *
     * Note, when processing an incoming request, the controller map will first be
     * checked to see if the request can be handled by one of the controllers in the map.
     * If not, a controller will be searched for under the {@link getControllerPath default controller path}.
     */
    public $controllerMap = array();
    /**
     * @var array the configuration specifying a controller which should handle
     * all user requests. This is mainly used when the application is in maintenance mode
     * and we should use a controller to handle all incoming requests.
     * The configuration specifies the controller route (the first element)
     * and GET parameters (the rest name-value pairs). For example,
     * <pre>
     * array(
     *     'offline/notice',
     *     'param1'=>'value1',
     *     'param2'=>'value2',
     * )
     * </pre>
     * Defaults to null, meaning catch-all is not effective.
     */
    public $catchAllRequest;

    /**
     * @var string Namespace that should be used when loading controllers.
     * Default is to use global namespace.
     * @since 1.1.11
     */
    public $controllerNamespace;

    private $_controllerPath;
    private $_viewPath;
    private $_systemViewPath;
    private $_layoutPath;
    private $_controller;
    private $_theme;


    /**
     * Processes the current request.
     * It first resolves the request into controller and action,
     * and then creates the controller to perform the action.
     */
    public function processRequest()
    {
        //Is there a valid cache file?  If so, we're done...
        if ($this->getHooks()->callHook('cacheOverride') === false) {
            if ($this->Output->displayCache()) {
                Fly::app()->end();
            }
        }

        $route = $this->Router->getRouting();
        $this->runController($route);
    }

    /**
     * @return CHttpSession the session component
     */
    public function getSession()
    {
        //return $this->getComponent('session');
    }

    /**
     * Returns the view renderer.
     * If this component is registered and enabled, the default
     * view rendering logic defined in {@link CBaseController} will
     * be replaced by this renderer.
     * @return IViewRenderer the view renderer.
     */
    public function getViewRenderer()
    {
        //return $this->getComponent('viewRenderer');
    }

    /**
     * Creates the controller and performs the specified action.
     * @param string $route the route of the current request. See {@link createController} for more details.
     * @throws CHttpException if the controller could not be created.
     */
    public function runController($route)
    {
        $controller = $this->createController($route);
        $uri = explode('/', $route);
        $action = end($uri);
        if ($controller !== null) {
            if (method_exists($controller, 'remap')) {
                $controller->remap($action, array_slice($this->Uri->getRsegments(), 2));
            } else {

                if (!method_exists($controller, $this->getActionName($action))) {

                    $rule = $this->Router->getRoutes('404_override');
                    // Check and see if we are using a 404 override and use it.
                    if (!empty($rule)) {
                        $x = explode('/', $rule);
                        $class = $this->getControllerName($x[0]);
                        $action = (isset($x[1]) ? $x[1] : 'index');
                        $this->Router->setModule('');
                        $this->Router->setClass($x[0]);
                        $this->Router->setMethod($action);
                        $filePath = Fly::app()->getControllerPath().DIRECTORY_SEPARATOR.$class.EXT;
                        if (!file_exists($filePath)) {
                            $this->showPageNotFound();
                        }
                        include_once($filePath);
                        if (!class_exists($class)) {
                            $this->showPageNotFound();
                        }
                        $this->_controller = $controller = new $class($x[0], $this);

                    }
                }

                // Call the requested method.
                // Any URI segments present (besides the class/function) will be passed to the method for convenience
                //call_user_func_array(array(&$controller, $action), array());
                $oldController = $this->_controller;
                $controller->init();
                $controller->run($action);
                $this->_controller = $oldController;
            }
        } else {
            $this->showPageNotFound();
        }

        // Mark a benchmark end point
        $this->Benchmark->mark('CONTROLLER_END');

        /**
         * Is there a "end of controller" hook.
         */
        $this->getHooks()->callHook('endController');

        /**
         * Send the final rendered output to the browser.
         */
        if ($this->getHooks()->callHook('displayOverride') === false) {
            $this->Output->display();
        }

        /**
         * Is there a "end of application" hook.
         */
        $this->Hooks->callHook('endApplication');

    }


    public function createController($route, $module = null)
    {
        if ($module === null) {
            $module = $this;
        }
        $uri = explode('/', $route);
        $subPath = '';
        $subUriCount = count($uri);
        if ($subUriCount == 3) {
            $subPath = $uri[0];
            $class = $uri[1];
        } else {
            $class = $uri[0];
        }

        $fileName = $this->getControllerName($class).EXT;
        $filePath = '';
        if ($module !== $this) {
            $filePath = $this->getModuleControllerPath($subPath).DIRECTORY_SEPARATOR.$fileName;
        } else {
            if ($subPath && $subPath !== '') {

                $moduleFilePath = $this->getModuleControllerPath($subPath).DIRECTORY_SEPARATOR.$fileName;

                if (file_exists($moduleFilePath)) {
                    $moduleName = $this->getModuleName($subPath);
                    Fly::import($subPath.'.'.$moduleName);
                    if (($module = $this->getModule($subPath)) !== null) {
                        $this->_controller = $this->createController($route, $module);
                        return $this->_controller;
                    }
                }

                $filePaths = $this->getModuleControllerPaths($subPath);
                foreach ($filePaths as $val) {
                    $path = $val.DIRECTORY_SEPARATOR.$fileName;
                    if (file_exists($path)) {
                        $filePath = $path;
                        break;
                    }
                }
            } else {
                $filePath = $this->getControllerPath().DIRECTORY_SEPARATOR.$fileName;
            }
        }

        // Load the local application controller
        // Note: The Router class automatically validates the controller path using the router->_validate_request().
        // If this include fails it means that the default controller in the Routes.php file is not resolving to something valid.
        if ($filePath === '') {
            throw new FlyException(Fly::t('fly', 'Unable to load your default controller. Please make sure the controller specified in your Routes.php file is valid.'));
        }

        if (file_exists($filePath)) {
            require_once($filePath);
        } else {
            $this->showPageNotFound();
        }

        // Set a mark point for benchmarking
        $this->getBenchmark()->mark('BASE_CLASS_INITIALIZATION_END');

        $oldClass = $class;
        $class = $this->getControllerName($class);
        if (!class_exists($class)) {
            $this->showPageNotFound();
        }

        /**
         * You can do something when controller will start.
         */
        $this->Hooks->callHook('startController');

        /**
         * Instantiate the requested controller
         * Mark a start point so we can benchmark the controller
         */
        $this->getBenchmark()->mark('CONTROLLER_START');
        $this->_controller = new $class($oldClass, $module);

        /**
         * You can do something when controller end of initialization.
         */
        $this->Hooks->callHook('endControllerInitialization');

        return $this->_controller;
    }

    /**
     * When the page does not exist, prompt message
     */
    public function showPageNotFound()
    {
        $msg = $this->Router->fetchClass().'/'.$this->Router->fetchMethod();
        $msg = $this->Router->fetchModule() === '' ? $msg : $this->Router->fetchModule().'/'.$msg;
        throw new HttpException(404, $msg);
    }

    /**
     * Parses a path info into an action ID and GET variables.
     * @param string $pathInfo path info
     * @return string action ID
     */
    protected function parseActionParams($pathInfo)
    {
        /*
        if(($pos=strpos($pathInfo,'/'))!==false)
        {
            $manager=$this->getUrlManager();
            $manager->parsePathInfo((string)substr($pathInfo,$pos+1));
            $actionID=substr($pathInfo,0,$pos);
            return $manager->caseSensitive ? $actionID : strtolower($actionID);
        }
        else
            return $pathInfo;
        */
    }

    /**
     * @return Controller the currently active controller
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * @param Controller $value the currently active controller
     */
    public function setController($value)
    {
        $this->_controller = $value;
    }

    /**
     * @return string the directory that contains the controller classes.
     * Defaults to 'application/controllers'.
     */
    public function getControllerPath()
    {
        if($this->_controllerPath !== null) {
            return $this->_controllerPath;
        } else {
            return $this->_controllerPath = $this->getBasePath().DIRECTORY_SEPARATOR.'controllers';
        }
    }

    /**
     * @param string $value the directory that contains the controller classes.
     * @throws FlyException if the directory is invalid
     */
    public function setControllerPath($value)
    {
        if(($this->_controllerPath = realpath($value)) === false || !is_dir($this->_controllerPath))
            throw new FlyException(Fly::t('fly','The controller path "{path}" is not a valid directory.',
                array('{path}'=>$value)));
    }

    /**
     * Create directory controller path
     *
     * @param $path
     * @return string
     */
    public function getDirectoryControllerPath($path)
    {
        $subPath = trim($path, '/');
        if ($subPath !== '') {
            $subPath = DIRECTORY_SEPARATOR.$subPath;
        }
        $fullPath = $this->getBasePath().DIRECTORY_SEPARATOR.'controllers'.$subPath;
        return $fullPath;
    }

    /**
     * Create module controller path
     *
     * @param $path
     * @return string
     */
    public function getModuleControllerPath($path)
    {
        $subPath = trim($path, '/');
        if ($subPath !== '') {
            $subPath = DIRECTORY_SEPARATOR.$subPath;
        }
        $fullPath = $this->getModulePath().$subPath.DIRECTORY_SEPARATOR.'controllers';
        Fly::setPathOfAlias($path, $this->getModulePath().$subPath);
        return $fullPath;
    }

    /**
     * Return current module controller search paths
     * @param $path
     * @return array
     */
    public function getModuleControllerPaths($path)
    {
        $searchPaths = array();
        $moduleAppName = $this->getId();
        if (!$moduleAppName || $moduleAppName === '') {
            $searchPaths[] = $this->getDirectoryControllerPath($path);
        }
        $searchPaths[] = $this->getModuleControllerPath($path);
        return $searchPaths;
    }

    /**
     * Return standard controller name
     *
     * @param $name
     * @return string
     */
    public function getControllerName($name)
    {
        $name = ucfirst($name).'Controller';
        return $name;
    }

    /**
     * Return standard action name
     *
     * @param $name string
     * @return string
     */
    public function getActionName($name)
    {
        $name = 'action'.ucfirst($name);
        return $name;
    }

    /**
     * @return string the root directory of view files. Defaults to 'application/views'.
     */
    public function getViewPath()
    {
        if ($this->_viewPath !== null) {
            return $this->_viewPath;
        } else {
            return $this->_viewPath = $this->getBasePath().DIRECTORY_SEPARATOR.'views';
        }
    }

    /**
     * @param string $path the root directory of view files.
     * @throws FlyException if the directory does not exist.
     */
    public function setViewPath($path)
    {
        if (($this->_viewPath = realpath($path)) === false || !is_dir($this->_viewPath)) {
            throw new FlyException(Fly::t('fly','The view path "{path}" is not a valid directory.',
                array('{path}'=>$path)));
        }
    }

    /**
     * @return string the root directory of system view files.
     * Defaults to 'application/views/system'.
     */
    public function getSystemViewPath()
    {
        if ($this->_systemViewPath !== null) {
            return $this->_systemViewPath;
        } else {
            return $this->_systemViewPath=$this->getViewPath().DIRECTORY_SEPARATOR.'system';
        }
    }

    /**
     * @param string $path the root directory of system view files.
     * @throws FlyException if the directory does not exist.
     */
    public function setSystemViewPath($path)
    {
        if (($this->_systemViewPath = realpath($path)) === false || !is_dir($this->_systemViewPath)) {
            throw new FlyException(Fly::t('fly','The system view path "{path}" is not a valid directory.',
                array('{path}'=>$path)));
        }
    }

    /**
     * @return string the root directory of layout files.
     * Defaults to 'application/views/layouts'.
     */
    public function getLayoutPath()
    {
        if ($this->_layoutPath !== null) {
            return $this->_layoutPath;
        } else {
            return $this->_layoutPath = $this->getViewPath().DIRECTORY_SEPARATOR.'layouts';
        }
    }

    /**
     * @param string $path the root directory of layout files.
     * @throws FlyException if the directory does not exist.
     */
    public function setLayoutPath($path)
    {
        if (($this->_layoutPath = realpath($path)) === false || !is_dir($this->_layoutPath)) {
            throw new FlyException(Fly::t('fly','The layout path "{path}" is not a valid directory.',
                array('{path}'=>$path)));
        }
    }

    /**
     * The pre-filter for controller actions.
     * This method is invoked before the currently requested controller action and all its filters
     * are executed. You may override this method with logic that needs to be done
     * before all controller actions.
     * @param Controller $controller the controller
     * @param string $action the action
     * @return boolean whether the action should be executed.
     */
    public function beforeControllerAction($controller, $action)
    {
        return true;
    }

    /**
     * The post-filter for controller actions.
     * This method is invoked after the currently requested controller action and all its filters
     * are executed. You may override this method with logic that needs to be done
     * after all controller actions.
     * @param Controller $controller the controller
     * @param string $action the action
     */
    public function afterControllerAction($controller, $action)
    {
    }

    /**
     * Do not call this method. This method is used internally to search for a module by its ID.
     * @param string $id module ID
     * @return Module the module that has the specified ID. Null if no module is found.
     */
    public function findModule($id)
    {
        if (($controller = $this->getController()) !== null && ($module = $controller->getModule()) !== null) {
            do {
                if (($m = $module->getModule($id)) !== null) {
                    return $m;
                }
            } while(($module = $module->getParentModule()) !== null);
        }
        if(($m = $this->getModule($id)) !== null) {
            return $m;
        }
    }

    /**
     * Initializes the application.
     * This method overrides the parent implementation by preloading the 'request' component.
     */
    protected function init()
    {
        /*
        parent::init();
        // preload 'request' so that it has chance to respond to onBeginRequest event.
        $this->getRequest();*/
    }
}
