<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

if (!function_exists('xml_parser_create')) {
    throw new FlyException(Fly::t('fly', 'Your PHP installation does not support XML'));
}

if (!class_exists('XmlRpc')) {
    throw new FlyException(Fly::t('fly', 'You must load the XmlRpc class before loading the XmlRpcServer class in order to create a server.'));
}

/**
 * XML-RPC server class
 */
class XmlRpcServer extends XmlRpc
{
    var $methods = array(); //array of methods mapped to function names and signatures
    var $debug_msg = ''; // Debug Message
    var $system_methods = array(); // XML RPC Server methods
    var $controller_obj;

    var $object = false;

    /**
     * Constructor
     */
    public function __construct($config = array())
    {
        parent::__construct();
        $this->setSystemMethods();

        if (isset($config['functions']) && is_array($config['functions'])) {
            $this->methods = array_merge($this->methods, $config['functions']);
        }

        Fly::log('debug', "XML-RPC Server Class Initialized");
    }

    /**
     * Initialize Prefs and Serve
     *
     * @param mixed
     * @return void
     */
    public function initialize($config = array())
    {
        if (isset($config['functions']) && is_array($config['functions'])) {
            $this->methods = array_merge($this->methods, $config['functions']);
        }

        if (isset($config['debug'])) {
            $this->debug = $config['debug'];
        }

        if (isset($config['object']) && is_object($config['object'])) {
            $this->object = $config['object'];
        }

        if (isset($config['xss_clean'])) {
            $this->xss_clean = $config['xss_clean'];
        }
    }

    /**
     * Setting of System Methods
     *
     * @return void
     */
    public function setSystemMethods()
    {
        $this->methods = array(
            'system.listMethods' => array(
                'function' => 'this.listMethods',
                'signature' => array(array($this->xmlrpcArray, $this->xmlrpcString), array($this->xmlrpcArray)),
                'docstring' => 'Returns an array of available methods on this server'
            ),
            'system.methodHelp' => array(
                'function' => 'this.methodHelp',
                'signature' => array(array($this->xmlrpcString, $this->xmlrpcString)),
                'docstring' => 'Returns a documentation string for the specified method'
            ),
            'system.methodSignature' => array(
                'function' => 'this.methodSignature',
                'signature' => array(array($this->xmlrpcArray, $this->xmlrpcString)),
                'docstring' => 'Returns an array describing the return type and required parameters of a method'
            ),
            'system.multicall' => array(
                'function' => 'this.multicall',
                'signature' => array(array($this->xmlrpcArray, $this->xmlrpcArray)),
                'docstring' => 'Combine multiple RPC calls in one request. See http://www.xmlrpc.com/discuss/msgReader$1208 for details'
            )
        );
    }

    /**
     * Main Server Function
     *
     * @return void
     */
    public function startService()
    {
        $r = $this->parseRequest();
        $payload = '<?xml version="1.0" encoding="'.$this->xmlrpcDefencoding.'"?'.'>'."\n";
        $payload .= $this->debug_msg;
        $payload .= $r->prepareResponse();

        header("Content-Type: text/xml");
        header("Content-Length: ".strlen($payload));
        exit($payload);
    }

    /**
     * Add Method to Class
     *
     * @param string $methodname method name
     * @param string $function
     * @param string $sig signature
     * @param string $doc docstring
     * @return void
     */
    public function addToMap($methodname, $function, $sig, $doc)
    {
        $this->methods[$methodname] = array(
            'function' => $function,
            'signature' => $sig,
            'docstring' => $doc
        );
    }

    /**
     * Parse Server Request
     *
     * @param string $data data
     * @return object xmlrpc response
     */
    public function parseRequest($data = '')
    {
        global $HTTP_RAW_POST_DATA;

        //-------------------------------------
        //  Get Data
        //-------------------------------------

        if ($data == '') {
            $data = $HTTP_RAW_POST_DATA;
        }

        //-------------------------------------
        //  Set up XML Parser
        //-------------------------------------

        $parser = xml_parser_create($this->xmlrpcDefencoding);
        $parser_object = new XmlRpcMessage("filler");

        $parser_object->xh[$parser] = array();
        $parser_object->xh[$parser]['isf'] = 0;
        $parser_object->xh[$parser]['isf_reason'] = '';
        $parser_object->xh[$parser]['params'] = array();
        $parser_object->xh[$parser]['stack'] = array();
        $parser_object->xh[$parser]['valuestack'] = array();
        $parser_object->xh[$parser]['method'] = '';

        xml_set_object($parser, $parser_object);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
        xml_set_element_handler($parser, 'openTag', 'closingTag');
        xml_set_character_data_handler($parser, 'characterData');
        //xml_set_default_handler($parser, 'default_handler');

        //-------------------------------------
        //  PARSE + PROCESS XML DATA
        //-------------------------------------

        if (!xml_parse($parser, $data, 1)) {
            // return XML error as a faultCode
            $r = new XmlRpcResponse(0,
                $this->xmlrpcerrxml + xml_get_error_code($parser),
                sprintf('XML error: %s at line %d',
                    xml_error_string(xml_get_error_code($parser)),
                    xml_get_current_line_number($parser)));
            xml_parser_free($parser);
        } else if ($parser_object->xh[$parser]['isf']) {
            return new XmlRpcResponse(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return']);
        } else {
            xml_parser_free($parser);

            $m = new XmlRpcMessage($parser_object->xh[$parser]['method']);
            $plist = '';

            for ($i = 0; $i < count($parser_object->xh[$parser]['params']); $i++) {
                if ($this->debug === true) {
                    $plist .= "$i - ".print_r(get_object_vars($parser_object->xh[$parser]['params'][$i]), true).";\n";
                }

                $m->addParam($parser_object->xh[$parser]['params'][$i]);
            }

            if ($this->debug === true) {
                echo "<pre>";
                echo "---PLIST---\n".$plist."\n---PLIST END---\n\n";
                echo "</pre>";
            }

            $r = $this->_execute($m);
        }

        //-------------------------------------
        //  SET DEBUGGING MESSAGE
        //-------------------------------------

        if ($this->debug === true) {
            $this->debug_msg = "<!-- DEBUG INFO:\n\n".$plist."\n END DEBUG-->\n";
        }

        return $r;
    }

    /**
     * Executes the Method
     *
     * @param object
     * @return mixed
     */
    protected function _execute($m)
    {
        $methName = $m->method_name;

        // Check to see if it is a system call
        $system_call = (strncmp($methName, 'system', 5) == 0) ? true : false;

        if ($this->xss_clean == false) {
            $m->xss_clean = false;
        }

        //-------------------------------------
        //  Valid Method
        //-------------------------------------

        if (!isset($this->methods[$methName]['function'])) {
            return new XmlRpcResponse(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
        }

        //-------------------------------------
        //  Check for Method (and Object)
        //-------------------------------------

        $method_parts = explode(".", $this->methods[$methName]['function']);
        $objectCall = (isset($method_parts['1']) && $method_parts['1'] != "") ? true : false;

        if ($system_call === true) {
            if (!is_callable(array($this, $method_parts['1']))) {
                return new XmlRpcResponse(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
            }
        } else {
            if ($objectCall && !is_callable(array($method_parts['0'], $method_parts['1']))) {
                return new XmlRpcResponse(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
            } else if (!$objectCall && !is_callable($this->methods[$methName]['function'])) {
                return new XmlRpcResponse(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
            }
        }

        //-------------------------------------
        //  Checking Methods Signature
        //-------------------------------------

        if (isset($this->methods[$methName]['signature'])) {
            $sig = $this->methods[$methName]['signature'];
            for ($i = 0; $i < count($sig); $i++) {
                $current_sig = $sig[$i];

                if (count($current_sig) == count($m->params) + 1) {
                    for ($n = 0; $n < count($m->params); $n++) {
                        $p = $m->params[$n];
                        $pt = ($p->kindOf() == 'scalar') ? $p->scalarval() : $p->kindOf();

                        if ($pt != $current_sig[$n + 1]) {
                            $pno = $n + 1;
                            $wanted = $current_sig[$n + 1];

                            return new XmlRpcResponse(0,
                                $this->xmlrpcerr['incorrect_params'],
                                $this->xmlrpcstr['incorrect_params'].
                                ": Wanted {$wanted}, got {$pt} at param {$pno})");
                        }
                    }
                }
            }
        }

        //-------------------------------------
        //  Calls the Function
        //-------------------------------------

        if ($objectCall === true) {
            if ($method_parts[0] == "this" && $system_call == true) {
                return call_user_func(array($this, $method_parts[1]), $m);
            } else {
                if ($this->object === false) {
                    return Fly::app()->Controller->$method_parts['1']($m);
                } else {
                    return $this->object->$method_parts['1']($m);
                    //return call_user_func(array(&$method_parts['0'],$method_parts['1']), $m);
                }
            }
        } else {
            return call_user_func($this->methods[$methName]['function'], $m);
        }
    }

    /**
     * Server Function:  List Methods
     *
     * @param mixed
     * @return object
     */
    public function listMethods($m)
    {
        $v = new XmlRpcValues();
        $output = array();

        foreach ($this->methods as $key => $value) {
            $output[] = new XmlRpcValues($key, 'string');
        }

        foreach ($this->system_methods as $key => $value) {
            $output[] = new XmlRpcValues($key, 'string');
        }

        $v->addArray($output);
        return new XmlRpcResponse($v);
    }

    /**
     * Server Function:  Return Signature for Method
     *
     * @param mixed
     * @return object
     */
    function methodSignature($m)
    {
        $parameters = $m->outputParameters();
        $method_name = $parameters[0];

        if (isset($this->methods[$method_name])) {
            if ($this->methods[$method_name]['signature']) {
                $sigs = array();
                $signature = $this->methods[$method_name]['signature'];

                for ($i = 0; $i < count($signature); $i++) {
                    $cursig = array();
                    $inSig = $signature[$i];
                    for ($j = 0; $j < count($inSig); $j++) {
                        $cursig[] = new XmlRpcValues($inSig[$j], 'string');
                    }
                    $sigs[] = new XmlRpcValues($cursig, 'array');
                }
                $r = new XmlRpcResponse(new XmlRpcValues($sigs, 'array'));
            } else {
                $r = new XmlRpcResponse(new XmlRpcValues('undef', 'string'));
            }
        } else {
            $r = new XmlRpcResponse(0, $this->xmlrpcerr['introspect_unknown'], $this->xmlrpcstr['introspect_unknown']);
        }
        return $r;
    }

    /**
     * Server Function:  Doc String for Method
     *
     * @param mixed
     * @return object
     */
    public function methodHelp($m)
    {
        $parameters = $m->outputParameters();
        $method_name = $parameters[0];

        if (isset($this->methods[$method_name])) {
            $docstring = isset($this->methods[$method_name]['docstring']) ? $this->methods[$method_name]['docstring'] : '';

            return new XmlRpcResponse(new XmlRpcValues($docstring, 'string'));
        } else {
            return new XmlRpcResponse(0, $this->xmlrpcerr['introspect_unknown'], $this->xmlrpcstr['introspect_unknown']);
        }
    }

    /**
     * Server Function:  Multi-call
     *
     * @param mixed
     * @return object
     */
    function multicall($m)
    {
        // Disabled
        return new XmlRpcResponse(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);

        $parameters = $m->outputParameters();
        $calls = $parameters[0];

        $result = array();

        foreach ($calls as $value) {
            //$attempt = $this->_execute(new XML_RPC_Message($value[0], $value[1]));

            $m = new XmlRpcMessage($value[0]);
            $plist = '';

            for ($i = 0; $i < count($value[1]); $i++) {
                $m->addParam(new XmlRpcValues($value[1][$i], 'string'));
            }

            $attempt = $this->_execute($m);

            if ($attempt->faultCode() != 0) {
                return $attempt;
            }

            $result[] = new XmlRpcValues(array($attempt->value()), 'array');
        }

        return new XmlRpcResponse(new XmlRpcValues($result, 'array'));
    }

    /**
     *  Multi-call Function:  Error Handling
     *
     * @param mixed
     * @return object
     */
    public function multicallError($err)
    {
        $str = is_string($err) ? $this->xmlrpcstr["multicall_${err}"] : $err->faultString();
        $code = is_string($err) ? $this->xmlrpcerr["multicall_${err}"] : $err->faultCode();

        $struct['faultCode'] = new XmlRpcValues($code, 'int');
        $struct['faultString'] = new XmlRpcValues($str, 'string');

        return new XmlRpcValues($struct, 'struct');
    }

    /**
     *  Multi-call Function:  Processes method
     *
     * @param mixed
     * @return object
     */
    public function doMultiCall($call)
    {
        if ($call->kindOf() != 'struct') {
            return $this->multicallError('notstruct');
        } else if (!$methName = $call->me['struct']['methodName']) {
            return $this->multicallError('nomethod');
        }

        list($scalar_type, $scalar_value) = each($methName->me);
        $scalar_type = $scalar_type == $this->xmlrpcI4 ? $this->xmlrpcInt : $scalar_type;

        if ($methName->kindOf() != 'scalar' OR $scalar_type != 'string') {
            return $this->multicallError('notstring');
        } else if ($scalar_value == 'system.multicall') {
            return $this->multicallError('recursion');
        } else if (!$params = $call->me['struct']['params']) {
            return $this->multicallError('noparams');
        } else if ($params->kindOf() != 'array') {
            return $this->multicallError('notarray');
        }

        list($a, $b) = each($params->me);
        $numParams = count($b);

        $msg = new XmlRpcMessage($scalar_value);
        for ($i = 0; $i < $numParams; $i++) {
            $msg->params[] = $params->me['array'][$i];
        }

        $result = $this->_execute($msg);

        if ($result->faultCode() != 0) {
            return $this->multicallError($result);
        }

        return new XmlRpcValues(array($result->value()), 'array');
    }
}