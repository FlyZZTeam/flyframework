<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Controller manages a set of actions which deal with the corresponding user requests.
 * Through the actions, Controller coordinates the data flow between models and views.
 *
 * @property string $id ID of the controller.
 * @property string $action The action currently being executed, null if no active action.
 * @property WebModule $module The module that this controller belongs to. It returns null
 * if the controller does not belong to any module.
 * @property string $viewPath The directory containing the view files for this controller. Defaults to 'application/views/ControllerID'.
 * @property string $pageTitle The page title. Defaults to the controller name and the action name.
 */
class Controller extends Component
{
    /**
     * @var mixed the name of the layout to be applied to this controller's views.
     * Defaults to null, meaning the {@link WebApplication::layout application layout}
     * is used. If it is false, no layout will be applied.
     * The {@link WebModule::layout module layout} will be used
     * if the controller belongs to a module and this layout property is null.
     */
    public $layout;
    /**
     * @var string the name of the default action. Defaults to 'index'.
     */
    public $defaultAction = 'index';

    private $_id;
    private $_action;
    private $_pageTitle;
    //private $_pageStates;
    //private $_dynamicOutput;
    private $_module;
    //private $_cachingStack;

    /**
     * Constructor
     * @param string $id id of this controller
     * @param WebModule $module the module that this controller belongs to.
     */
    public function __construct($id, $module = null)
    {
        $this->_id = $id;
        $this->_module = $module;
    }

    /**
     * Initializes the controller.
     * This method is called by the application before the controller starts to execute.
     * You may override this method to perform the needed initialization for the controller.
     */
    public function init()
    {
    }

    /**
     * Runs the action.
     * @param $action string
     */
    public function run($action)
    {
        $priorAction = $this->_action;
        $this->_action = $action;
        $action = Fly::app()->getActionName($action);
        if (method_exists($this, $action)) {
            if (($parent = $this->getModule()) === null) {
                $parent = Fly::app();
            }
            if ($parent->beforeControllerAction($this, $action)) {
                if ($this->beforeActionRun($action)) {
                    $this->$action();
                    $this->afterActionRun($action);
                    $parent->afterControllerAction($this, $action);
                }
            }
        } else {
            $this->missingActionMethod();
        }
        $this->_action = $priorAction;
    }

    /**
     * @return Module the module that this controller belongs to. It returns null
     * if the controller does not belong to any module
     */
    public function getModule()
    {
        return $this->_module;
    }

    /**
     * When the action does not exist, we can do some processing on this method
     */
    public function missingActionMethod()
    {
        Fly::app()->showPageNotFound();
    }

    /**
     * @return string the controller id.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return string the action method.
     */
    public function getAction()
    {
        $this->_action;
    }

    /**
     * @return string the page title. Defaults to the controller name and the action name.
     */
    public function getPageTitle()
    {
        if ($this->_pageTitle !== null) {
            return $this->_pageTitle;
        } else {
            $name = ucfirst(basename($this->getId()));
            if ($this->getAction() !== null && strcasecmp($this->getAction(), $this->defaultAction)) {
                return $this->_pageTitle = Fly::app()->name.' - '.ucfirst($this->getAction()).' '.$name;
            } else {
                return $this->_pageTitle = Fly::app()->name.' - '.$name;
            }
        }
    }

    /**
     * @param string $value the page title.
     */
    public function setPageTitle($value)
    {
        $this->_pageTitle = $value;
    }

    /**
     * Processes the request using another controller action.
     * This is like {@link redirect}, but the user browser's URL remains unchanged.
     * In most cases, you should call {@link redirect} instead of this method.
     * @param string $route the route of the new controller action. This can be an action ID, or a complete route
     * with module ID (optional in the current module), controller ID and action ID. If the former, the action is assumed
     * to be located within the current controller.
     * @param boolean $exit whether to end the application after this call. Defaults to true.
     */
    public function forward($route, $exit = true)
    {
        if (strpos($route, '/') === false) {
            $this->run($route);
        } else {
            if ($route[0] !== '/' && ($module = $this->getModule()) !== null) {
                $route = $module->getId().'/'.$route;
            }
            Fly::app()->runController($route);
        }

        if ($exit) {
            Fly::app()->end();
        }
    }

    /**
     * Postprocesses the output generated by {@link render()}.
     * This method is invoked at the end of {@link render()} and {@link renderText()}.
     *
     * @param string $output the output generated by the current action
     * @return string the output that has been processed.
     */
    public function processOutput($output)
    {
        return $output;
    }

    /**
     * Returns the directory containing view files for this controller.
     * The default implementation returns 'application/views/ControllerID'.
     * Child classes may override this method to use customized view path.
     * If the controller belongs to a module, the default view path
     * is the {@link Module::getViewPath module view path} appended with the controller ID.
     * @return string the directory containing the view files for this controller.
     * Defaults to 'application/views/ControllerID'.
     */
    public function getViewPath()
    {
        if (($module = $this->getModule()) === null) {
            $module = Fly::app();
        }
        return $module->getViewPath().DIRECTORY_SEPARATOR.$this->getId();
    }

    /**
     * Looks for the view file according to the given view name
     *
     * @param string $viewName view name
     * @return string the view file path, false if the view file does not exist
     * @see resolveViewFile
     * @see Application::findLocalizedFile
     */
    public function getViewFile($viewName)
    {
        $moduleViewPath = $basePath = Fly::app()->getViewPath();
        if (($module = $this->getModule()) !== null) {
            $moduleViewPath = $module->getViewPath();
        }

        return $this->resolveViewFile($viewName, $this->getViewPath(), $basePath, $moduleViewPath);
    }

    /**
     * Looks for the layout view script based on the layout name.
     *
     * The layout name can be specified in one of the following ways:
     *
     * <ul>
     * <li>layout is false: returns false, meaning no layout.</li>
     * <li>layout is null: the currently active module's layout will be used. If there is no active module,
     * the application's layout will be used.</li>
     * <li>a regular view name.</li>
     * </ul>
     *
     * The resolution of the view file based on the layout view is similar to that in {@link getViewFile}.
     *
     * @param mixed $layoutName layout name
     * @return string the view file for the layout. False if the view file cannot be found
     */
    public function getLayoutFile($layoutName)
    {
        if ($layoutName === false) {
            return false;
        }

        if (empty($layoutName)) {
            $module = $this->getModule();
            while ($module !== null) {
                if ($module->layout === false) {
                    return false;
                }
                if (!empty($module->layout)) {
                    break;
                }
                $module = $module->getParentModule();
            }
            if ($module === null) {
                $module = Fly::app();
            }
            $layoutName = $module->layout;
        } else if (($module = $this->getModule()) === null) {
            $module = Fly::app();
        }

        return $this->resolveViewFile($layoutName, $module->getLayoutPath(), Fly::app()->getViewPath(), $module->getViewPath());
    }

    /**
     * Finds a view file based on its name.
     *
     * @param string $viewName the view name
     * @param string $viewPath the directory that is used to search for a relative view name
     * @param string $basePath the directory that is used to search for an absolute view name under the application
     * @param string $moduleViewPath the directory that is used to search for an absolute view name under the current module.
     * If this is not set, the application base view path will be used.
     * @return mixed the view file path. False if the view file does not exist.
     */
    public function resolveViewFile($viewName, $viewPath, $basePath, $moduleViewPath = null)
    {

        if ($moduleViewPath === null) {
            $moduleViewPath = $basePath;
        }
        $extension = '.php';
        if ($viewName[0] === '/') {
            if (strncmp($viewName, '//', 2) === 0) {
                $viewFile = $basePath.$viewName;
            } else {
                $viewFile = $moduleViewPath.$viewName;
            }
        } else if (strpos($viewName, '.')) {
            $viewFile = Fly::getPathOfAlias($viewName);
        } else {
            $viewFile = $viewPath.DIRECTORY_SEPARATOR.$viewName;
        }

        if (is_file($viewFile.$extension)) {
            return Fly::app()->findLocalizedFile($viewFile.$extension);
        }
        return false;
    }

    /**
     * Renders a view file.
     *
     * @param string $viewFile view file path
     * @param array $data data to be extracted and made available to the view
     * @param boolean $return whether the rendering result should be returned instead of being echoed
     * @return string the rendering result. Null if the rendering result is not required.
     * @throws FlyException if the view file does not exist
     */
    public function renderFile($viewFile, $data = null, $return = false)
    {
        $content = $this->renderInternal($viewFile, $data, $return);
        return $content;
    }

    /**
     * Renders a view file.
     * This method includes the view file as a PHP script
     * and captures the display result if required.
     *
     * @param string $_viewFile_ view file
     * @param array $_data_ data to be extracted and made available to the view file
     * @param boolean $_return_ whether the rendering result should be returned as a string
     * @return string the rendering result. Null if the rendering result is not required.
     */
    public function renderInternal($_viewFile_, $_data_ = null, $_return_ = false)
    {
        // we use special variable names here to avoid conflict when extracting data
        if (is_array($_data_)) {
            extract($_data_, EXTR_PREFIX_SAME, 'data');
        } else {
            $data = $_data_;
        }
        if ($_return_) {
            ob_start();
            ob_implicit_flush(false);
            require($_viewFile_);
            return ob_get_clean();
        } else {
            require($_viewFile_);
        }
    }

    /**
     * Renders a view with a layout.
     *
     * This method first calls {@link renderPartial} to render the view (called content view).
     * It then renders the layout view which may embed the content view at appropriate place.
     * In the layout view, the content view rendering result can be accessed via variable
     * <code>$content</code>. At the end, it calls {@link processOutput} to insert scripts
     * and dynamic contents if they are available.
     *
     * By default, the layout view script is "application/views/layouts/main.php".
     * This may be customized by changing {@link layout}.
     *
     * @param string $view name of the view to be rendered. See {@link getViewFile} for details
     * about how the view script is resolved.
     * @param array $data data to be extracted into PHP variables and made available to the view script
     * @param boolean $return whether the rendering result should be returned instead of being displayed to end users.
     * @return string the rendering result. Null if the rendering result is not required.
     * @see renderPartial
     * @see getLayoutFile
     */
    public function render($view, $data = null, $return = false)
    {
        if ($this->beforeRender($view)) {
            $output = $this->renderPartial($view, $data, true);
            if (($layoutFile = $this->getLayoutFile($this->layout)) !== false) {
                $output = $this->renderFile($layoutFile, array('content' => $output), true);
            }

            $this->afterRender($view, $output);

            $output = $this->processOutput($output);

            if ($return) {
                return $output;
            } else {
                $this->displayOutput($output);
            }
        }
    }

    /**
     * This method is displayed output data
     * You may override this method to do some thing when display a view.
     * @param $output
     */
    protected function displayOutput($output)
    {
        Fly::app()->Output->appendOutput($output);
    }

    /**
     * This method is invoked at the beginning of {@link render()}.
     * You may override this method to do some preprocessing when rendering a view.
     * @param string $view the view to be rendered
     * @return boolean whether the view should be rendered.
     */
    protected function beforeRender($view)
    {
        return true;
    }

    /**
     * This method is invoked after the specified is rendered by calling {@link render()}.
     * Note that this method is invoked BEFORE {@link processOutput()}.
     * You may override this method to do some postprocessing for the view rendering.
     * @param string $view the view that has been rendered
     * @param string $output the rendering result of the view. Note that this parameter is passed
     * as a reference. That means you can modify it within this method.
     */
    protected function afterRender($view, &$output)
    {
    }

    /**
     * Renders a static text string.
     * The string will be inserted in the current controller layout and returned back.
     * @param string $text the static text string
     * @param boolean $return whether the rendering result should be returned instead of being displayed to end users.
     * @return string the rendering result. Null if the rendering result is not required.
     * @see getLayoutFile
     */
    public function renderText($text, $return = false)
    {
        if (($layoutFile = $this->getLayoutFile($this->layout)) !== false) {
            $text = $this->renderFile($layoutFile, array('content' => $text), true);
        }

        $text = $this->processOutput($text);

        if ($return) {
            return $text;
        } else {
            echo $text;
        }
    }

    /**
     * Renders a view.
     *
     * The named view refers to a PHP script (resolved via {@link getViewFile})
     * that is included by this method. If $data is an associative array,
     * it will be extracted as PHP variables and made available to the script.
     *
     * This method differs from {@link render()} in that it does not
     * apply a layout to the rendered result. It is thus mostly used
     * in rendering a partial view, or an AJAX response.
     *
     * @param string $view name of the view to be rendered. See {@link getViewFile} for details
     * about how the view script is resolved.
     * @param array $data data to be extracted into PHP variables and made available to the view script
     * @param boolean $return whether the rendering result should be returned instead of being displayed to end users
     * @param boolean $processOutput whether the rendering result should be postprocessed using {@link processOutput}.
     * @return string the rendering result. Null if the rendering result is not required.
     * @throws FlyException if the view does not exist
     * @see getViewFile
     * @see processOutput
     * @see render
     */
    public function renderPartial($view, $data = null, $return = false, $processOutput = false)
    {
        if (($viewFile = $this->getViewFile($view)) !== false) {
            $output = $this->renderFile($viewFile, $data, true);
            if ($processOutput) {
                $output = $this->processOutput($output);
            }
            if ($return) {
                return $output;
            } else {
                echo $output;
            }
        } else {
            throw new FlyException(Fly::t('fly', '{controller} cannot find the requested view "{view}".',
                array('{controller}' => get_class($this), '{view}' => $view)));
        }
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * You may override this method to do last-minute preparation for the action.
     * @param $action string the action to be executed.
     * @return boolean whether the action should be executed.
     */
    protected function beforeActionRun($action)
    {
        return true;
    }

    /**
     * This method is invoked right after an action is executed.
     * You may override this method to do some postprocessing for the action.
     * @param $action string the action just executed.
     */
    protected function afterActionRun($action)
    {
    }

    /**
     * Creates a relative URL for the specified action defined in this controller.
     * @param string $route the URL route. This should be in the format of 'ControllerID/ActionID'.
     * If the ControllerID is not present, the current controller ID will be prefixed to the route.
     * If the route is empty, it is assumed to be the current action.
     * If the controller belongs to a module, the {@link WebModule::getId module ID}
     * will be prefixed to the route. (If you do not want the module ID prefix, the route should start with a slash '/'.)
     * @param array $params additional GET parameters (name=>value). Both the name and value will be URL-encoded.
     * If the name is '#', the corresponding value will be treated as an anchor
     * and will be appended at the end of the URL.
     * @param string $ampersand the token separating name-value pairs in the URL.
     * @return string the constructed URL
     */
    public function createUrl($route, $params = array(), $ampersand = '&')
    {
        if ($route === '') {
            $route = $this->getId().'/'.$this->getAction();
        } else if (strpos($route, '/') === false) {
            $route = $this->getId().'/'.$route;
        }
        if ($route[0] !== '/' && ($module = $this->getModule()) !== null) {
            $route = $module->getId().'/'.$route;
        }
        return Fly::app()->createUrl(trim($route, '/'), $params, $ampersand);
    }

    /**
     * Creates an absolute URL for the specified action defined in this controller.
     * @param string $route the URL route. This should be in the format of 'ControllerID/ActionID'.
     * If the ControllerPath is not present, the current controller ID will be prefixed to the route.
     * If the route is empty, it is assumed to be the current action.
     * @param array $params additional GET parameters (name=>value). Both the name and value will be URL-encoded.
     * @param string $schema schema to use (e.g. http, https). If empty, the schema used for the current request will be used.
     * @param string $ampersand the token separating name-value pairs in the URL.
     * @return string the constructed URL
     */
    public function createAbsoluteUrl($route, $params = array(), $schema = '', $ampersand = '&')
    {
        $url = $this->createUrl($route, $params, $ampersand);
        if (strpos($url, 'http') === 0) {
            return $url;
        } else {
            return Fly::app()->Request->getHostUrl($schema).$url;
        }
    }

    /**
     * Redirects the browser to the specified URL or route (controller/action).
     * @param mixed $url the URL to be redirected to. If the parameter is an array,
     * the first element must be a route to a controller action and the rest
     * are GET parameters in name-value pairs.
     * @param boolean $terminate whether to terminate the current application after calling this method. Defaults to true.
     * @param integer $statusCode the HTTP status code. Defaults to 302. See {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
     * for details about HTTP status code.
     */
    public function redirect($url, $terminate = true, $statusCode = 302)
    {
        if (is_array($url)) {
            $route = isset($url[0]) ? $url[0] : '';
            $url = $this->createUrl($route, array_splice($url, 1));
        }
        Fly::app()->getRequest()->redirect($url, $terminate, $statusCode);
    }

    /**
     * Refreshes the current page.
     * The effect of this method call is the same as user pressing the
     * refresh button on the browser (without post data).
     * @param boolean $terminate whether to terminate the current application after calling this method
     * @param string $anchor the anchor that should be appended to the redirection URL.
     * Defaults to empty. Make sure the anchor starts with '#' if you want to specify it.
     */
    public function refresh($terminate = true, $anchor = '')
    {
        $this->redirect(Fly::app()->getRequest()->getUrl().$anchor, $terminate);
    }
}