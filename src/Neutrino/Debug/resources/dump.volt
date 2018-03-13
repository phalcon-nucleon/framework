{% set id = uniqid('nuc-dump-') %}
<style>
  pre.nuc-dump {
    margin:0 0 5px 0;
    padding: 5px;
    background: #232525;
    color: #f5f5f5;
    line-height: 1.5;
    font: 12px monospace;
    text-align: left;
    word-wrap: break-word;
    white-space: pre-wrap;
    position: relative;
    z-index: 99999;
    word-break: break-all;
  }
  pre.nuc-dump code {
    color: #a69730;
  }
  pre.nuc-dump ul {
    margin:0;
    padding:0;
    list-style-type: none;
  }
  pre.nuc-dump ul li {
    margin:0;
    padding:0;
    list-style-type: none;
  }
  pre.nuc-dump ul li {
    margin-left: 15px;
  }
  pre.nuc-dump small {
    font-size: 80%;
  }

  pre.nuc-dump li.nuc-close > *:not(ul),
  pre.nuc-dump li.nuc-open > *:not(ul) {
    cursor: pointer;
  }
  pre.nuc-dump li.nuc-close > ul {
    display: none;
  }
  pre.nuc-dump li.nuc-open > ul {
    display: inherit;
  }
  pre.nuc-dump span.nuc-open, pre.nuc-dump span.nuc-close {
    cursor: pointer;
  }
  pre.nuc-dump code.nuc-key {
    color: #a69730;
  }
  pre.nuc-dump .nuc-modifier {
    color: #6897BB;
  }
  pre.nuc-dump code.nuc-array, pre.nuc-dump code.nuc-const {
    color: #CC7832;
  }
  pre.nuc-dump code.nuc-integer, pre.nuc-dump code.nuc-float, pre.nuc-dump code.nuc-double {
    color:#90caf9;
  }
  pre.nuc-dump code.nuc-string {
    color:#629755;
  }
  pre.nuc-dump code.nuc-string:before, pre.nuc-dump code.nuc-string:after {
    content:'"';
    color: #CC7832;
  }
   pre.nuc-dump code.nuc-object {
    color: #a032cc;
  }
</style>

<pre class="nuc-dump" id="{{ id }}">
{{ __dump(var) }}
</pre>

<script>
  (function (document) {
    var pre = document.getElementById('{{ id }}');

    pre.addEventListener('click', function (ev) {
      var target = ev.target, tag = target.tagName;
      var li;
      if (tag === 'LI') {
        li = target;
      } else {
        li = target.parentElement;
      }
      if (li.tagName !== 'LI') {
        return;
      }
      if (li && li.querySelector('ul')){
        li.classList.toggle('nuc-close');
        li.classList.toggle('nuc-open')
      }
    })
  })(document)
</script>
