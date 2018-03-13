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
    private $dumped = [];

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

        if (is_array($var) && in_array($var, $this->dumped, true)) {
            $dump = '<code  class="nuc-' . gettype($var) . '">';
            $dump .= 'array</code> *RECURSION*';
            $this->lvl--;

            return $dump;
        }

        $id = $this->objId($var);

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
            $dump = '<code class="nuc-resource">resource</code>';
        } elseif (is_array($var)) {
            $this->dumped[] = $var;
            $dump = '<code class="nuc-array">array:' . count($var) . '</code> <span class="nuc-closure">[</span>';
            if (!empty($var)) {
                $dump .= '<span class="nuc-toggle nuc-toggle-array"></span>';
                $dump .= '<ul class="nuc-array">';
                foreach ($var as $key => $val) {
                    $dump .= '<li class="nuc-' . gettype($val) . ' ' . (is_array($val) || is_object($val) ? 'nuc-close' : '') . '">';
                    $dump .= $this->__dump($key) . ' <span class="nuc-sep">=></span> ';
                    $dump .= $this->__dump($val);
                    $dump .= '</li>';
                }
                $dump .= '</ul>';
            }
            $dump .= '<span class="nuc-closure nuc-close">]</span>';

            array_pop($this->dumped);
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
                $prop .= '<li class="nuc-' . $ctype . ' ' . (is_array($val) || is_object($val) ? 'nuc-close' : '') . '">';
                $prop .= '<code class="nuc-key" title="' . $type . ' ' . $name . ':' . $vtype . '"><small class="nuc-modifier">' . $mod . '</small> ' . $name . '</code>: ';
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
        if (!is_object($var)) {
            return null;
        }

        $spl = spl_object_hash($var);

        if (isset($this->hash[$spl])) {
            return $this->hash[$spl];
        }

        return $this->hash[$spl] = self::uid();
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

        flush();ob_flush();
    }

    private static function outputBasic()
    {
        static $outputted;

        if (isset($outputted)) {
            return;
        }

        $outputted = true;

        echo '<style>pre.nuc-dump{margin:0 0 5px;padding:5px;background:#232525;color:#eee;line-height:1.5;font:12px monospace;text-align:left;word-wrap:break-word;white-space:pre-wrap;word-break:break-all;position:relative;z-index:99999}pre.nuc-dump code,pre.nuc-dump code.nuc-key{color:#a69730}pre.nuc-dump ul{margin:0;padding:0;list-style-type:none;position:relative}pre.nuc-dump ul::before{content:" ";display:block;position:absolute;width:0;top:0;bottom:0;left:2px;border-left:1px dotted rgba(255,255,255,.15)}pre.nuc-dump ul li{margin:0 0 0 15px;padding:0;list-style-type:none}pre.nuc-dump small{font-size:80%}pre.nuc-dump li.nuc-close>ul{display:none}pre.nuc-dump li.nuc-open>ul{display:inherit}pre.nuc-dump .nuc-toggle{padding:0 2px;cursor:pointer;color:#919292;}pre.nuc-dump .nuc-toggle:hover{color:#fefefe;}pre.nuc-dump .nuc-open .nuc-toggle::after{font:10px sans-serif;content:"▼"}pre.nuc-dump .nuc-close .nuc-toggle::after{font:10px sans-serif;content:"►"}pre.nuc-dump .nuc-toggle.nuc-toggle-object{border-radius:2px}pre.nuc-dump .nuc-toggle.nuc-toggle-object:hover{background:rgba(255,255,255,.2);border-radius:2px}pre.nuc-dump .nuc-open .nuc-toggle.nuc-toggle-object::after{content:" ▼"}pre.nuc-dump .nuc-close .nuc-toggle.nuc-toggle-object::after{content:" ►"}pre.nuc-dump .nuc-modifier{color:#c16b2a}pre.nuc-dump code.nuc-const{color:#CC7832}pre.nuc-dump code.nuc-double,pre.nuc-dump code.nuc-float,pre.nuc-dump code.nuc-integer{color:#90caf9}pre.nuc-dump code.nuc-string{color:#52b33b}pre.nuc-dump code.nuc-string.nuc-truncate{cursor:pointer}pre.nuc-dump .nuc-closure,pre.nuc-dump .nuc-sep{color:#ef6c00}pre.nuc-dump code.nuc-string.nuc-truncate::after{color:#d800ff;font-weight:700;line-height:11px;content:\' >\'}pre.nuc-dump code.nuc-string.nuc-truncate.nuc-open::after{content:\' <\'}pre.nuc-dump code.nuc-array{color:#CC7832}pre.nuc-dump code.nuc-object{color:#00b0ff}</style>';
        echo '<script>window.nucDumper=window.nucDumper||function(f){function e(a,b){if(a===b)return!1;var c=a.parentElement;return c===b?!0:c?e(c,b):!1}function d(a){a&&a.querySelector("ul")&&(a.classList.toggle("nuc-close"),a.classList.toggle("nuc-open"))}function g(a){a=a.target;var b=a.tagName,c=a.classList;"CODE"===a.tagName&&c.contains("nuc-truncate")?(c.toggle("nuc-open"),a.innerText=c.contains("nuc-open")?a.dataset.a:a.dataset.a.substr(0,117)):"SPAN"===b&&a.hasAttribute("data-target")?(b=f.getElementById(a.getAttribute("data-target")),e(a,b)||(b.parentNode===a.parentNode?d(b.parentElement):((b=b.parentElement)&&b.querySelector("ul")&&(b.classList.add("nuc-close"),b.classList.remove("nuc-open")),a.parentNode.insertBefore(f.getElementById(a.getAttribute("data-target")),a.nextSibling),d(a.parentElement)))):"SPAN"===b&&c.contains("nuc-toggle")&&(a=a.parentElement,"LI"===a.tagName&&d(a))}return function(a){a=f.getElementById(a);for(var b=a.querySelectorAll("code.nuc-string"),c,d=0,e=b.length;d<e;d++)c=b[d],120<c.innerText.length&&(c.classList.add("nuc-truncate"),c.dataset.a=c.innerText,c.innerText=c.innerText.substr(0,117));a.addEventListener("click",g)}}(document);</script>';
    }
}
