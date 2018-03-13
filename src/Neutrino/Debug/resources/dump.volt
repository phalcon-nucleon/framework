{% set id = uniqid('nuc-dump-') %}
<style>
  pre.nuc-dump {
    margin:0 0 5px 0;
    padding: 5px;
    background: #232525;
    color: #eeeeee;
    line-height: 1.5;
    font: 12px monospace;
    text-align: left;
    word-wrap: break-word;
    white-space: pre-wrap;
    word-break: break-all;
    position: relative;
    z-index: 99999;
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
  pre.nuc-dump li.nuc-close > ul {
    display: none;
  }
  pre.nuc-dump li.nuc-open > ul {
    display: inherit;
  }
  pre.nuc-dump span.nuc-closure.nuc-open {
    cursor: pointer;
  }
  pre.nuc-dump span.nuc-closure.nuc-open::after {
    cursor: pointer;
    color:#d800ff;
    font-weight: bold;
  }
  pre.nuc-dump .nuc-open span.nuc-closure.nuc-open::after {
    content:"-";
  }
  pre.nuc-dump .nuc-close span.nuc-closure.nuc-open::after {
    content:"+";
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
  pre.nuc-dump code.nuc-string.nuc-truncate {
    cursor: pointer;
  }
  pre.nuc-dump .nuc-sep {
    color: #CC7832;
  }

  pre.nuc-dump code.nuc-string.nuc-truncate::after {
    color:#d800ff;
    font-weight: bold;
    line-height: 11px;
    content: ' >';
  }
  pre.nuc-dump code.nuc-string.nuc-truncate.nuc-open::after {
    content: ' <';
  }

  pre.nuc-dump code.nuc-object {
    color: #ea80fc;
  }
</style>

<pre class="nuc-dump" id="{{ id }}">
{{ __dump(var) }}
</pre>

<script>
  (function (document) {
    var pre = document.getElementById('{{ id }}');
    var elements = pre.querySelectorAll('code.nuc-string'), element;

    for (var i = 0, l = elements.length; i < l; i++) {
      element = elements[i];
      if (element.innerText.length > 120) {
        element.classList.add('nuc-truncate');
        element.dataset.contentStr = element.innerText;
        element.innerText = element.innerText.substr(0, 117);
      }
    }

    pre.addEventListener('click', function (ev) {
      var target = ev.target, tag = target.tagName, classList = target.classList;

      if (target.tagName === 'CODE' && classList.contains('nuc-truncate')) {
        classList.toggle('nuc-open');
        if (classList.contains('nuc-open')) {
          target.innerText = target.dataset.contentStr;
        } else {
          target.innerText = target.dataset.contentStr.substr(0, 117);
        }
      } else if (tag === 'SPAN' && target.hasAttribute('data-target')) {
        target.parentNode.appendChild(document.getElementById(target.getAttribute('data-target')));
      } else if (tag === 'SPAN' && classList.contains('nuc-closure') && classList.contains('nuc-open')) {
        var li;
        li = target.parentElement;
        if (li.tagName !== 'LI') {
          return;
        }
        if (li && li.querySelector('ul')) {
          li.classList.toggle('nuc-close');
          li.classList.toggle('nuc-open')
        }
      }
    });
  })(document)
</script>
