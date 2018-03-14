<?php

namespace Neutrino\Debug;

use Neutrino\Constants\Services;
use Phalcon\Di;

/**
 * Class Dumper
 *
 * @package Neutrino\Debug
 */
class Dumper
{

    private $dumpRefs = [];

    private $hash = [];

    private $lvl = 0;

    private function __construct()
    {
    }

    private function __dump($var)
    {
        $this->lvl++;

        if ($this->lvl > 128) {
            $dump = '<span>** MAX DUMP LVL **</span>';
            $this->lvl--;

            return $dump;
        }

        $id = $this->objId($var);

        if (is_array($var) && isset($this->dumpRefs[$id])) {
            $dump = '<code  class="nuc-array">';
            $dump .= 'array:' . count($var) . ' </code> <span class="nuc-closure">[</span><span class="nuc-toggle nuc-toggle-array" data-target="nuc-ref-' . $id . '"></span>';
            $dump .= '<span class="nuc-closure nuc-close">]</span>';
            $this->lvl--;

            return $dump;
        }

        if (is_object($var) && isset($this->dumpRefs[$id])) {
            $dump = '<code class="nuc-' . gettype($var) . '">';
            $class = (preg_replace('/.*\\\\(\w+)$/', '$1', get_class($var)));
            $dump .= $class . '</code> <span class="nuc-closure">{</span><span class="nuc-toggle nuc-toggle-object" data-target="nuc-ref-' . $id . '">#' . $id . '</span>';
            $dump .= '<span class="nuc-closure nuc-close">}</span>';
            $this->lvl--;

            return $dump;
        }

        if (is_null($var)) {
            $dump = '<code class="nuc-const">null</code>';
        } elseif (is_bool($var)) {
            $dump = '<code class="nuc-const">' . ($var ? 'true' : 'false') . '</code>';
        } elseif (is_string($var)) {
            $dump = '<span class="nuc-sep">"</span><code class="nuc-string" title="' . strlen($var) . ' characters">' . htmlentities($var) . '</code><span class="nuc-sep">"</span>';
        } elseif (is_scalar($var)) {
            $dump = '<code class="nuc-' . gettype($var) . '">' . $var . '</code>';
        } elseif (is_resource($var)) {
            $dump = '<code class="nuc-resource">' . get_resource_type($var) . '</code>';
        } elseif (is_array($var)) {
            $dump = '<code class="nuc-array">array:' . count($var) . '</code> <span class="nuc-closure">[</span>';
            if(!empty($var)){
                if(!is_null($id)){
                    $this->dumpRefs[$id] = true;
                    $dump .= '<span class="nuc-toggle nuc-toggle-array" data-target="nuc-ref-' . $id . '"></span>';
                    $dump .= '<ul class="nuc-array" id="nuc-ref-' . $id . '">';
                } else {
                    $dump .= '<span class="nuc-toggle nuc-toggle-array"></span>';
                    $dump .= '<ul class="nuc-array">';
                }
                foreach ($var as $key => $val) {
                    $dump .= '<li class="nuc-' . gettype($val) . ' ' . (is_array($val) || is_object($val) ? 'nuc-close' : '') . '">';
                    $dump .= $this->__dump($key) . ' <span class="nuc-sep">=></span> ';
                    $dump .= $this->__dump($val);
                    $dump .= '</li>';
                }
                $dump .= '</ul>';
            }
            $dump .= '<span class="nuc-closure nuc-close">]</span>';
        } elseif (is_object($var)) {
            $this->dumpRefs[$id] = true;

            $class = get_class($var);
            $dump = '<code class="nuc-object" title="'.$class.'">' . (preg_replace('/.*\\\\(\w+)$/', '$1', $class)) . '</code> ';
            $dump .= '<span class="nuc-closure">{</span>';

            $prop = '';
            $properties = (new \ReflectionClass(get_class($var)))->getProperties();
            $dumpedProperties = [];
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $val = $property->getValue($var);
                $dumpedProperties[] = $name = $property->getName();
                $isStatic = $property->isStatic();

                if ($property->isPrivate()) {
                    $type='private';
                    $mod = '-';
                } elseif ($property->isProtected()) {
                    $type='protected';
                    $mod = '#';
                } else {
                    $type='public';
                    $mod = '+';
                }
                $ctype = gettype($val);
                $vtype = $ctype ==='object' ? get_class($val) : $ctype;
                $title = $type . ' ' . ($isStatic ? 'static ' : '') . $name . ':' . $vtype;
                $prop .= '<li class="nuc-' . $ctype . ' ' . (is_array($val) || is_object($val) ? 'nuc-close' : '') . '">';
                $prop .= '<code class="nuc-key" title="' . $title . '"><small class="nuc-modifier">' . $mod . '</small> ' . ($isStatic ? '::' : '') . $name . '</code>: ';
                $prop .= $this->__dump($val);
                $prop .= '</li>';
            }
            foreach ($var as $key => $val) {
                if (!in_array($key, $dumpedProperties, true)) {
                    $ctype = gettype($val);
                    $vtype = $ctype ==='object' ? get_class($val) : $ctype;
                    $prop .= '<li class="nuc-' . gettype($val) . ' ' . (is_array($val) || is_object($val) ? 'nuc-close' : '') . '">';
                    $prop .= '<code class="nuc-key" title="public ' . $key . ':' . $vtype . '"><small class="nuc-modifier">+</small> ' . $key . '</code>: ';
                    $prop .= $this->__dump($val);
                    $prop .= '</li>';
                }
            }
            if (!empty($prop)) {
                $dump .= '<span class="nuc-toggle nuc-toggle-object" data-target="nuc-ref-' . $id . '">#' . $id . '</span>';
                $dump .= '<ul class="nuc-object" id="nuc-ref-' . $id . '">';
                $dump .= $prop;
                $dump .= '</ul>';
            }
            $dump .= '<span class="nuc-closure">}</span>';
        } else {
            $dump = '';
        }

        $this->lvl--;

        return $dump;
    }

    private function objId($var)
    {
        if (is_object($var)) {
            $hash = spl_object_hash($var);
        } elseif (is_array($var) && $this->arrIsComplex($var)) {
            $hash = $this->arrId($var);
        }

        if (isset($hash)) {
            if (isset($this->hash[$hash])) {
                return $this->hash[$hash];
            }

            return $this->hash[$hash] = self::uid();
        }

        return null;
    }

    private function arrId(array $var)
    {
        return md5($this->arrHash($var));
    }

    private function arrHash(array $var)
    {
        static $dump;

        if(!isset($dump)){
            $dump = [];
        }

        if (in_array($var, $dump, true)) {
            return 'array recursion';
        }

        $dump[] = $var;

        $hash = [];
        foreach ($var as $k => $v) {
            if(is_object($v)){
                $hash[$k] = spl_object_hash($v);
            } elseif(is_array($v)){
                $hash[$k] = $this->arrHash($v);
            } elseif (is_resource($v)){
                $hash[$k] = intval($v) . get_resource_type($v);
            } else {
                $hash[$k] = $v;
            }
        }

        $hash = json_encode($hash);

        array_pop($dump);

        return $hash;
    }

    private function arrIsComplex(array $var)
    {
        static $dump;

        if(!isset($dump)){
            $dump = [];
        }

        if (in_array($var, $dump, true)) {
            return true;
        }

        $dump[] = $var;

        $return = false;

        foreach ($var as $item) {
            if(is_object($item)){
                $return = true;
                break;
            } elseif(is_array($item) && $this->arrIsComplex($item)){
                $return = true;
                break;
            }
        }

        array_pop($dump);

        return $return;
    }

    private static function uid()
    {
        static $uid;

        if (!isset($uid)) {
            $uid = 0;
        }

        return ++$uid;
    }

    public static function dump($var)
    {
        // We force the start of the session so that it is initialized before the first exit.
        switch (session_status()){
            case PHP_SESSION_DISABLED:
            case PHP_SESSION_ACTIVE:
                break;
            default:
                $di = Di::getDefault();
                if($di->has(Services::SESSION)){
                    $di->get(Services::SESSION);
                } else {
                    session_start();
                }
        }

        self::outputBasic();

        $id = 'nuc-dump-' . self::uid();

        echo '<pre class="nuc-dump" id="' . $id . '">' . (new self)->__dump($var) . '</pre>';
        echo '<script>nucDumper("' . $id . '")</script>';
    }

    private static function outputBasic()
    {
        static $outputted;

        if (isset($outputted)) {
            return;
        }

        $outputted = true;

        echo '<style>pre.nuc-dump{margin:0 0 5px;padding:5px;background:#232525;color:#eee;line-height:1.5;font:12px monospace;text-align:left;word-wrap:break-word;white-space:pre-wrap;word-break:break-all;position:relative;z-index:99999}pre.nuc-dump code,pre.nuc-dump code.nuc-key{color:#a69730}pre.nuc-dump ul{margin:0;padding:0;list-style-type:none;position:relative}pre.nuc-dump ul::before{content:" ";display:block;position:absolute;width:0;top:0;bottom:0;left:2px;border-left:1px dotted rgba(255,255,255,.15)}pre.nuc-dump ul li{margin:0 0 0 15px;padding:0;list-style-type:none}pre.nuc-dump small{font-size:80%}pre.nuc-dump li.nuc-close>ul{display:none}pre.nuc-dump li.nuc-open>ul{display:inherit}pre.nuc-dump .nuc-toggle{padding:0 2px;cursor:pointer;color:#919292;border-radius:2px}pre.nuc-dump .nuc-toggle:hover{color:#fefefe}pre.nuc-dump .nuc-open .nuc-toggle::after{font:10px sans-serif;content:" ▼"}pre.nuc-dump .nuc-close .nuc-toggle::after{font:10px sans-serif;content:" ►"}pre.nuc-dump .nuc-parent:after{content:""!important}pre.nuc-dump .nuc-toggle-object:hover{background:rgba(255,255,255,.2)}pre.nuc-dump .nuc-hover{background:#8b18a7!important;color:#fefefe!important}pre.nuc-dump .nuc-modifier{color:#c16b2a}pre.nuc-dump code.nuc-const{color:#CC7832}pre.nuc-dump code.nuc-double,pre.nuc-dump code.nuc-float,pre.nuc-dump code.nuc-integer{color:#90caf9}pre.nuc-dump code.nuc-string{color:#52b33b}pre.nuc-dump code.nuc-string.nuc-truncate{cursor:pointer}pre.nuc-dump .nuc-closure,pre.nuc-dump .nuc-sep{color:#ef6c00}pre.nuc-dump code.nuc-string.nuc-truncate::after{color:#d800ff;font-weight:700;line-height:11px;content:\' >\'}pre.nuc-dump code.nuc-string.nuc-truncate.nuc-open::after{content:\' <\'}pre.nuc-dump code.nuc-array{color:#CC7832}pre.nuc-dump code.nuc-object{color:#00b0ff}</style>';
        echo '<script>window.nucDumper=window.nucDumper||function(f){function g(a,b){var c=a.parentNode;return c.id===b?!0:"PRE"===c.tagName?!1:g(c,b)}function h(a){var b;var c=a.querySelectorAll(".nuc-parent");var d=0;for(b=c.length;d<b;d++)c[d].classList.remove("nuc-parent");c=a.querySelectorAll("[data-target]");d=0;for(b=c.length;d<b;d++)a=c[d],g(a,a.dataset.target)&&a.classList.add("nuc-parent")}function k(a,b){if(a===b)return!1;var c=a.parentElement;return c===b?!0:c?k(c,b):!1}function e(a){a&&a.querySelector("ul")&&(a.classList.toggle("nuc-close"),a.classList.toggle("nuc-open"))}function l(a){a=a.target;var b=a.tagName,c=a.classList;"CODE"===a.tagName&&c.contains("nuc-truncate")?(c.toggle("nuc-open"),a.innerText=c.contains("nuc-open")?a.dataset.a:a.dataset.a.substr(0,117)):"SPAN"===b&&a.hasAttribute("data-target")?(b=f.getElementById(a.getAttribute("data-target")),k(a,b)||(b.parentNode===a.parentNode?e(b.parentElement):((b=b.parentElement)&&b.querySelector("ul")&&(b.classList.add("nuc-close"),b.classList.remove("nuc-open")),a.parentNode.insertBefore(f.getElementById(a.getAttribute("data-target")),a.nextSibling),e(a.parentElement),h(this)))):"SPAN"===b&&c.contains("nuc-toggle")&&(a=a.parentElement,"LI"===a.tagName&&e(a))}return function(a){a=f.getElementById(a);for(var b=a.querySelectorAll("code.nuc-string"),c,d=0,e=b.length;d<e;d++)c=b[d],120<c.innerText.length&&(c.classList.add("nuc-truncate"),c.dataset.a=c.innerText,c.innerText=c.innerText.substr(0,117));a.addEventListener("click",l);a.addEventListener("mouseover",function(a){a=a.target;a.classList.contains("nuc-parent")&&(a.classList.add("nuc-hover"),document.getElementById(a.dataset.target).previousElementSibling.classList.add("nuc-hover"))});a.addEventListener("mouseout",function(a){a=a.target;a.classList.contains("nuc-parent")&&(a.classList.remove("nuc-hover"),document.getElementById(a.dataset.target).previousElementSibling.classList.remove("nuc-hover"))});h(a)}}(document);</script>';
    }
}
