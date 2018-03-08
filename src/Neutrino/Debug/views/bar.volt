<style type="text/css" rel="stylesheet">#nuc-debug-bar{position:relative;z-index:99999;-webkit-font-smoothing:subpixel-antialiased;-moz-osx-font-smoothing:auto;line-height:1.5;font-weight:300;font-family:Arial, sans-serif;font-size:15px}#nuc-debug-bar [class^=http-1],#nuc-debug-bar [class^=http-2]{background-color:#66bb6a}#nuc-debug-bar [class^=http-3]{background-color:#fbc02d}#nuc-debug-bar [class^=http-4]{background-color:#f4511e}#nuc-debug-bar [class^=http-5]{background-color:#c62828}#nuc-debug-bar small{font-size:75%}#nuc-debug-bar table,#nuc-debug-bar tr,#nuc-debug-bar td{font-size:13px;border:none}#nuc-debug-bar table{width:100%;display:table;border-collapse:collapse;border-spacing:0}#nuc-debug-bar table.bordered>thead>tr{font-size:13px;border-bottom:1px solid #8a8a8a}#nuc-debug-bar table.bordered>tbody>tr{border-bottom:1px groove #8a8a8a}#nuc-debug-bar table tr{background-color:#212121 !important}#nuc-debug-bar table tr th,#nuc-debug-bar table tr td{padding:5px 10px;border-radius:0;display:table-cell;text-align:left;vertical-align:middle}#nuc-debug-bar table tr th small,#nuc-debug-bar table tr td small{font-size:11px}#nuc-debug-bar .collection{margin:0;padding:0}#nuc-debug-bar pre{white-space:pre-line;word-break:break-all;font-size:12px !important;margin:0;line-height:1.4}#nuc-debug-bar .nuc-debug-bar-nav{background-color:#212121;color:#fafafa;position:fixed;left:0;right:0;bottom:0;height:35px;line-height:35px}#nuc-debug-bar .nuc-debug-bar-nav ul{margin:0;padding:0;list-style-type:none}#nuc-debug-bar .nuc-debug-bar-nav ul li{list-style-type:none;transition:background-color .3s;float:left;padding:0;position:relative}#nuc-debug-bar .nuc-debug-bar-nav ul li.active{background-color:rgba(0,0,0,0.1)}#nuc-debug-bar .nuc-debug-bar-nav ul li>span,#nuc-debug-bar .nuc-debug-bar-nav ul li>a,#nuc-debug-bar .nuc-debug-bar-nav ul li>small{display:block;padding:0 7px;font-size:13px;height:35px}#nuc-debug-bar .nuc-debug-bar-nav ul li .dropup-content{display:none}#nuc-debug-bar .nuc-debug-bar-nav ul li:hover>.dropup-content{line-height:1.5;display:inherit;position:absolute;bottom:35px}#nuc-debug-bar .nuc-debug-bar-nav ul li:hover>.dropup-content.bottom-sheet{position:fixed;left:0;right:0;overflow:auto;max-height:calc(100vh / 2.5)}#nuc-debug-bar .nuc-debug-bar-nav ul li>a,#nuc-debug-bar .nuc-debug-bar-nav ul li>span{transition:background-color .3s;color:#fafafa;display:block;cursor:pointer;text-decoration:none}#nuc-debug-bar .nuc-debug-bar-nav ul li>a i,#nuc-debug-bar .nuc-debug-bar-nav ul li>a svg,#nuc-debug-bar .nuc-debug-bar-nav ul li>span i,#nuc-debug-bar .nuc-debug-bar-nav ul li>span svg{position:relative;top:6px}#nuc-debug-bar .nuc-debug-bar-nav ul li>a .bag,#nuc-debug-bar .nuc-debug-bar-nav ul li>span .bag{font-weight:300;font-size:0.8rem;color:#fff;background-color:#26a69a;border-radius:2px;min-width:1rem;padding:0 8px;margin-top:7px;margin-left:3px;text-align:center;line-height:22px;height:22px;float:right;box-sizing:border-box}#nuc-debug-bar .nuc-debug-bar-nav ul li>a:hover,#nuc-debug-bar .nuc-debug-bar-nav ul li>span:hover{background-color:rgba(0,0,0,0.1)}#nuc-debug-bar .nuc-debug-bar-nav ul.left{float:left}#nuc-debug-bar .nuc-debug-bar-nav ul.right{float:right}#nuc-debug-bar .sql .string{color:#a5d6a7 !important}#nuc-debug-bar .sql .table{color:#90caf9 !important}#nuc-debug-bar .sql .column{color:#ce93d8 !important}#nuc-debug-bar .sql .func{color:#fdd835 !important}#nuc-debug-bar .sql .keyw{color:#fb8c00 !important}#nuc-debug-bar .event .space{color:#90caf9 !important}#nuc-debug-bar .event .type{color:#ce93d8 !important}#nuc-debug-bar .slow-request{background:#ffab00}#nuc-debug-bar .no-errors .bag{background-color:#81c784 !important}#nuc-debug-bar .with-errors{background-color:#f4511e !important}#nuc-debug-bar .with-errors .bag{background-color:#b71c1c !important}#nuc-debug-modal.nuc-debug-modal-wrapper{display:none;position:fixed;top:0;right:0;left:0;bottom:0;background-color:rgba(0,0,0,0.6);overflow:auto;z-index:9999999;font-size:14px;font-weight:normal;font-family:Arial, sans-serif;line-height:1.5}#nuc-debug-modal.nuc-debug-modal-wrapper *,#nuc-debug-modal.nuc-debug-modal-wrapper *:before,#nuc-debug-modal.nuc-debug-modal-wrapper *:after{box-sizing:inherit}#nuc-debug-modal .debug-modal{display:none;margin-left:auto;margin-right:auto;margin-top:20px;max-width:1280px;width:90%;background-color:#212121;padding:15px;color:#eee}@media only screen and (min-width: 601px){ #nuc-debug-modal .debug-modal{width:85%}}@media only screen and (min-width: 993px){ #nuc-debug-modal .debug-modal{width:70%}}#nuc-debug-modal #debug-build-info ul{margin:0;padding:0;list-style-type:none}#nuc-debug-modal #debug-build-info ul li{color:#fafafa !important;list-style-type:none;padding:15px 10px;background:rgba(255,255,255,0.1);cursor:pointer;box-sizing:inherit}#nuc-debug-modal #debug-build-info ul li:not(:last-child){margin-bottom:10px}#nuc-debug-modal #debug-build-info ul li span,#nuc-debug-modal #debug-build-info ul li code{display:inline-block;text-align:left}#nuc-debug-modal #debug-build-info ul li span{width:20%;position:relative}#nuc-debug-modal #debug-build-info ul li span i,#nuc-debug-modal #debug-build-info ul li span svg{position:relative;top:6px}#nuc-debug-modal #debug-build-info ul li>div{display:none;padding:15px 0 0 15px}#nuc-debug-modal #debug-build-info ul li>div>p{display:table-row}#nuc-debug-modal #debug-build-info ul li>div>p>span,#nuc-debug-modal #debug-build-info ul li>div>p>code{display:table-cell;width:auto;padding:2px 5px}#nuc-debug-modal #debug-build-info ul li>div>p>span{white-space:nowrap;word-break:keep-all}#nuc-debug-modal #debug-build-info ul li>div>p>code{word-break:break-all}#nuc-debug-modal #debug-build-info ul li.open>div{display:table}body{padding-bottom:35px !important}body.nuc-debug-modal-open{overflow:hidden}body.nuc-debug-modal-open #nuc-debug-modal.nuc-debug-modal-wrapper{display:block;overflow-y:scroll}body.nuc-debug-modal-open #nuc-debug-modal.nuc-debug-modal-wrapper .debug-modal.open{display:block}
</style>
{% macro neutrinoIcon() %}
<svg xmlns="http://www.w3.org/2000/svg"
fill="#d0d0d0" height="24" width="24"
xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"
x="0px" y="0px" viewBox="0 0 486.4 486.4"
style="enable-background:new 0 0 486.4 486.4;"
xml:space="preserve">
<g>
<circle cx="233.124" cy="218.925" r="22.732"></circle>
<circle cx="253.276" cy="295.697" r="22.732"></circle>
<circle cx="265.272" cy="251.074" r="22.732"></circle>
<circle cx="220.888" cy="263.309" r="22.732"></circle>
<path
d="M297.504,251.074c0-8.61-3.353-16.704-9.441-22.792c-6.267-6.266-14.493-9.406-22.724-9.423   c-0.017-8.232-3.157-16.458-9.424-22.725c-12.567-12.567-33.016-12.567-45.583,0c-10.552,10.552-12.24,26.658-5.072,38.993   c-2.568,1.423-4.986,3.214-7.163,5.391c-12.567,12.568-12.567,33.016,0,45.583c6.284,6.284,14.538,9.425,22.792,9.425   c0.058,0,0.117-0.004,0.175-0.004c-0.045,8.312,3.093,16.639,9.421,22.967c6.284,6.284,14.538,9.425,22.792,9.425   s16.508-3.142,22.792-9.425c10.632-10.633,12.267-26.905,4.908-39.275c2.54-1.416,4.931-3.193,7.087-5.349   C294.151,267.777,297.504,259.683,297.504,251.074z M278.504,251.074c0,3.534-1.377,6.857-3.876,9.356   c-5.158,5.159-13.554,5.159-18.712,0c-2.499-2.499-3.876-5.822-3.876-9.356c0-3.535,1.377-6.857,3.876-9.356   c2.579-2.58,5.968-3.87,9.356-3.87s6.777,1.29,9.356,3.87C277.127,244.216,278.504,247.539,278.504,251.074z M223.768,209.569   c2.579-2.58,5.968-3.87,9.356-3.87s6.777,1.29,9.356,3.87c5.159,5.159,5.159,13.554,0,18.713c-5.157,5.16-13.553,5.16-18.712,0   C218.609,223.123,218.609,214.728,223.768,209.569z M211.532,272.665c-5.159-5.159-5.159-13.553,0-18.712   c2.579-2.58,5.968-3.87,9.356-3.87s6.777,1.29,9.356,3.87c5.159,5.159,5.159,13.553,0,18.712   C225.086,277.824,216.69,277.824,211.532,272.665z M262.632,305.053c-5.158,5.159-13.554,5.159-18.712,0   c-5.159-5.159-5.159-13.554,0-18.713c2.579-2.58,5.967-3.869,9.356-3.869c3.388,0,6.777,1.29,9.356,3.869   C267.791,291.5,267.791,299.894,262.632,305.053z"
></path>
<g>
<path
d="M50.647,204.112c-3.024,0-5.998-1.441-7.839-4.122c-2.971-4.325-1.872-10.239,2.453-13.21    c6.002-4.122,12.618-8.094,19.663-11.803c4.643-2.446,10.388-0.662,12.832,3.98c2.445,4.643,0.662,10.388-3.98,12.832    c-6.391,3.365-12.367,6.95-17.76,10.654C54.374,203.57,52.5,204.112,50.647,204.112z"
></path>
</g>
<g>
<path
d="M288.794,466.598c-1.294,0-2.609-0.266-3.868-0.828c-6.224-2.779-12.626-6.117-19.028-9.922    c-4.511-2.681-5.994-8.51-3.314-13.02c2.68-4.51,8.51-5.995,13.02-3.313c5.771,3.429,11.513,6.426,17.068,8.906    c4.791,2.139,6.94,7.757,4.801,12.548C295.896,464.502,292.427,466.598,288.794,466.598z"
></path>
</g>
<g>
<path
d="M267.909,78.168c-3.188,0-6.3-1.604-8.095-4.515c-2.754-4.466-1.366-10.319,3.1-13.073    c7.18-4.427,14.359-8.271,21.338-11.426c4.783-2.162,10.409-0.038,12.57,4.744c2.161,4.781,0.037,10.409-4.743,12.569    c-6.244,2.822-12.701,6.282-19.193,10.285C271.332,77.711,269.61,78.168,267.909,78.168z"
></path>
</g>
<circle cx="106.064" cy="438.425" r="29.114"></circle>
<circle cx="152.336" cy="47.975" r="29.114"></circle>
<circle cx="446.836" cy="312.075" r="29.114"></circle>
<path
d="M486.4,257.311c0-33.426-26.239-64.255-73.885-86.808c-3.481-1.648-7.058-3.231-10.707-4.764   c0.497-3.927,0.914-7.817,1.228-11.655c4.292-52.538-9.288-90.677-38.236-107.39c-13.694-7.907-29.852-10.476-48.017-7.637   c-5.184,0.81-8.73,5.669-7.919,10.853c0.809,5.185,5.674,8.733,10.853,7.919c13.774-2.152,25.742-0.361,35.583,5.319   c21.908,12.649,32.406,45.23,28.799,89.389c-0.168,2.059-0.375,4.138-0.6,6.226c-22.398-7.717-47.222-13.476-73.533-17.093   c-23.708-30.533-49.772-56.083-76.245-74.394c-14.827-10.255-29.24-17.854-42.946-22.751c-0.774-8.709-4.482-17.203-11.133-23.854   c-15.057-15.055-39.554-15.053-54.609,0c-7.293,7.293-11.309,16.99-11.309,27.304c0,1.324,0.068,2.637,0.199,3.936   c-23.641,18.719-34.45,54.523-30.557,102.173c2.62,32.082,11.715,67.43,26.304,103.228c-10.023,24.594-17.447,48.972-21.963,72.228   c-1.921-0.849-3.825-1.709-5.692-2.593C41.968,307.99,19,282.609,19,257.311c0-10.611,3.878-21.157,11.527-31.347   c3.151-4.196,2.302-10.151-1.894-13.3c-4.196-3.151-10.15-2.302-13.301,1.894C5.158,228.11,0,242.494,0,257.311   c0,33.426,26.239,64.255,73.885,86.808c3.481,1.648,7.058,3.231,10.707,4.764c-0.497,3.927-0.914,7.817-1.228,11.655   c-1.373,16.802-0.908,32.123,1.293,45.749c-2.083,1.388-4.062,2.997-5.898,4.833c-15.055,15.056-15.055,39.553,0,54.608   c7.529,7.528,17.416,11.291,27.305,11.291c7.352,0,14.702-2.085,21.085-6.246c8.746,3.963,18.36,5.933,28.668,5.933   c23.145,0,49.78-9.909,77.904-29.36c26.473-18.31,52.538-43.861,76.245-74.394c26.311-3.617,51.135-9.376,73.533-17.093   c0.225,2.088,0.432,4.167,0.6,6.226c3.607,44.159-6.89,76.74-28.799,89.389c-8.809,5.085-19.362,7.059-31.357,5.862   c-5.231-0.515-9.875,3.29-10.395,8.511c-0.52,5.221,3.29,9.875,8.51,10.395c2.921,0.291,5.787,0.436,8.595,0.436   c12.613,0,24.067-2.931,34.147-8.75c28.948-16.713,42.527-54.852,38.236-107.39c-0.314-3.838-0.731-7.728-1.228-11.655   c3.649-1.533,7.227-3.117,10.707-4.764c2.7-1.278,5.322-2.586,7.883-3.916c7.413,6.971,16.922,10.467,26.437,10.467   c9.889,0,19.777-3.763,27.305-11.291c13.628-13.629,14.919-34.994,3.873-50.089C483.535,279.047,486.4,268.321,486.4,257.311z    M380.717,177.916c-3.194,17.631-8.198,36.025-14.873,54.736c-5.641-11.856-11.854-23.69-18.615-35.402   c-6.762-11.711-13.904-23.008-21.351-33.822C345.419,167.003,363.851,171.866,380.717,177.916z M356.158,257.311   c-7.228,16.869-15.724,33.83-25.383,50.561c-9.66,16.731-20.101,32.568-31.095,47.263c-18.223,2.175-37.16,3.298-56.479,3.298   c-19.298,0-38.215-1.121-56.42-3.291c-11.083-14.789-21.558-30.648-31.155-47.27c-9.66-16.731-18.155-33.692-25.383-50.561   c7.228-16.869,15.724-33.83,25.383-50.561c9.581-16.594,20.048-32.44,31.124-47.222c18.332-2.199,37.284-3.339,56.451-3.339   c19.319,0,38.256,1.123,56.479,3.298c10.995,14.694,21.436,30.532,31.095,47.263C340.434,223.481,348.929,240.442,356.158,257.311z    M133.334,43.344c1.998-8.776,10.001-15.105,19.003-15.102c7.844,0.002,15.103,5.023,18.121,12.223   c1.842,4.394,2.014,9.442,0.272,13.895c-1.381,3.528-2.698,5.846-6.38,9.026c-6.565,5.089-15.95,5.401-22.861,0.806   C134.71,59.684,131.537,51.24,133.334,43.344z M102.301,152.537c-3.017-36.932,3.839-65.756,18.977-81.604   c1.132,1.525,2.385,2.978,3.753,4.346c7.529,7.528,17.416,11.291,27.304,11.291c9.889,0,19.777-3.763,27.305-11.291   c3.439-3.439,6.083-7.375,7.951-11.574c11.224,4.333,23.103,10.748,35.32,19.197c20.636,14.273,41.067,33.296,60.254,55.867   c-13.088-1.042-26.443-1.58-39.967-1.58c-13.437,0-26.784,0.541-39.912,1.593c8.019-9.43,16.27-18.272,24.669-26.403   c3.77-3.65,3.868-9.664,0.218-13.434c-3.65-3.769-9.663-3.867-13.433-0.218c-13.228,12.805-26.086,27.273-38.285,42.974   c-20.115,2.771-39.46,6.785-57.496,11.996c-5.04,1.457-7.946,6.723-6.489,11.764c1.456,5.04,6.72,7.947,11.764,6.489   c11.563-3.342,23.706-6.164,36.263-8.464c-7.475,10.843-14.614,22.132-21.33,33.763c-6.762,11.711-12.974,23.546-18.615,35.402   C110.602,204.75,104.344,177.544,102.301,152.537z M120.556,281.97c5.641,11.856,11.854,23.69,18.615,35.402   c6.732,11.66,13.888,22.967,21.376,33.826c-19.551-3.575-37.991-8.44-54.865-14.492   C108.877,319.075,113.881,300.681,120.556,281.97z M116.337,455.129c-7.63,4.673-17.813,3.494-24.142-2.835   c-5.784-5.783-7.374-14.812-3.879-22.219c3.543-7.51,11.583-11.987,19.817-11.125c8.074,0.845,14.975,6.779,16.955,14.665   C127.146,441.821,123.56,450.705,116.337,455.129z M222.912,431.72c-31.471,21.767-60.716,30.093-82.221,23.813   c2.602-5.254,3.986-11.073,3.986-17.108c0-10.314-4.016-20.011-11.309-27.304c-8.31-8.309-19.494-12.022-30.38-11.159   c-1.561-11.315-1.818-24.025-0.687-37.876c0.168-2.059,0.375-4.138,0.6-6.226c22.412,7.722,47.252,13.483,73.581,17.1   c12.579,16.187,25.861,31.057,39.533,44.162c3.787,3.629,9.802,3.504,13.433-0.285c3.63-3.787,3.502-9.802-0.285-13.432   c-8.811-8.445-17.457-17.677-25.851-27.545c13.063,1.038,26.391,1.574,39.887,1.574c13.523,0,26.879-0.537,39.967-1.58   C263.98,398.424,243.548,417.448,222.912,431.72z M325.878,351.194c7.447-10.813,14.589-22.11,21.351-33.822   c6.762-11.711,12.974-23.546,18.615-35.402c6.675,18.711,11.679,37.104,14.873,54.736   C363.851,342.756,345.419,347.619,325.878,351.194z M454.018,330.321c-7.217,2.82-15.712,1.041-21.159-4.488   c-5.723-5.809-7.254-14.863-3.726-22.225c3.58-7.472,11.756-11.99,19.987-10.999c9.758,1.174,17.33,9.612,17.33,19.466   C466.45,320.046,461.451,327.417,454.018,330.321z M462.836,276.937c-14.235-6.463-31.615-3.854-43.304,7.834   c-7.293,7.293-11.309,16.99-11.309,27.304c0,4.152,0.661,8.199,1.915,12.034c-1.88,0.959-3.788,1.908-5.751,2.837   c-1.867,0.884-3.771,1.744-5.692,2.593c-4.516-23.256-11.94-47.634-21.963-72.228c10.023-24.594,17.447-48.972,21.963-72.228   c1.921,0.849,3.825,1.709,5.692,2.593c40.046,18.956,63.014,44.337,63.014,69.635C467.4,263.886,465.835,270.465,462.836,276.937z"></path>
</g>
</svg>
{% endmacro %}
{% macro phalconIcon() %}
<svg xmlns="http://www.w3.org/2000/svg"
xmlns:xlink="http://www.w3.org/1999/xlink" width="20"
height="24" viewBox="0 0 256 292" version="1.1"
preserveAspectRatio="xMidYMid">
<g>
<path
d="M203.573531,139.85597 L185.825037,104.10178 L191.483981,128.023648 L203.573531,139.85597 Z"
fill="#73B08F"/>
<path
d="M182.744685,91.5718826 L196.445333,149.447359 L152.388348,102.889756 L88.1833618,42.9564729 L71.7963155,0 L182.744685,91.5718826 Z"
fill="#C5E4D3"/>
<path
d="M155.917143,104.324072 L98.2232384,76.6641846 L66.7270865,42.8278208 L62.6060728,20.5387846 L155.917143,104.324072 Z"
fill="#76C39B"/>
<path
d="M200.050714,151.984676 L145.586642,138.100739 L40.8480608,77.8496676 L0,21.7899753 L157.107886,105.093631 L200.050714,151.984676 Z"
fill="#000000"/>
<path
d="M143.090857,136.501153 L143.090857,138.109887 L136.056191,151.247954 L99.3196049,139.182384 L31.0572621,87.9708193 L4.48185922,49.8972965 L34.9654096,68.129688 L39.6552009,75.3690191 L143.090857,136.501153 Z"
fill="#73B08F"/>
<path
d="M44.121314,98.0093387 L39.0396997,97.4187853 L21.2540639,90.9227038 L57.6722521,124.288949 L67.2708505,131.67086 L105.382915,144.367747 L44.121314,98.0093387 Z"
stroke="#000000" stroke-width="1.12244904" fill="#000000"/>
<path
d="M193.432742,149.497171 L193.432742,149.497171 L192.661066,179.067331 L189.831608,174.233765 L173.369235,173.096438 L135.300032,151.203132 L144.045665,135.84941 L193.432742,149.497171 Z"
fill="#76C39B"/>
<path
d="M38.4981481,116.924238 L85.8847635,153.13742 L135.104021,169.519589 L173.065682,172.968453 L135.365825,151.125575 L38.4981481,116.924238 Z"
fill="#C5E4D3"/>
<path
d="M61.4766266,145.772128 L94.4013587,164.035068 L132.727788,167.893431 L86.427402,153.488869 L78.9678909,148.858816 L61.4766266,145.772128 Z"
fill="#76C39B"/>
<path
d="M134.641623,167.151704 L80.7684577,162.452545 L108.521303,173.103947 L126.751109,175.610158 L134.641623,167.151704 Z"
fill="#73B08F"/>
<path
d="M122.438812,175.456973 L117.808773,176.139578 L96.2019254,179.211306 L106.748124,172.043949 L122.438812,175.456973 Z"
fill="#76C39B"/>
<path
d="M156.946049,180.124768 L155.891143,181.995156 L77.036564,227.686322 L61.4766266,248.260699 L67.0149077,226.083118 L84.1572112,202.569532 L133.210557,169.436768 L156.946049,180.124768 Z"
fill="#76C39B"/>
<path
d="M70.7367044,202.618716 L56.8465877,220.881656 L56.8465877,212.907699 L70.7367044,202.618716 Z"
fill="#000000"/>
<path
d="M185.20157,183.326885 L132.470564,219.081075 L128.097749,211.621578 L109.834812,250.205226 L113.950405,255.86417 L117.0371,291.61836 L94.9158042,250.719686 L112.921514,206.477079 L156.906876,179.211306 L185.20157,183.326885 Z"
fill="#C5E4D3"/>
<path
d="M112.921514,205.705418 L77.6817698,224.740019 L76.6528644,250.976901 L87.4562932,286.216646 L87.4562932,268.210936 L112.921514,205.705418 Z"
fill="#000000"/>
<path
d="M69.1933676,256.121386 L69.7078132,257.921967 L74.3378521,277.471014 L68.1644621,273.61265 L69.1933676,256.121386 Z"
fill="#73B08F"/>
<path
d="M133.384043,216.570559 L128.657059,210.736648 L108.914943,248.126706 L140.33549,289.229251 L132.549871,270.931973 L133.384043,216.570559 Z"
fill="#73B08F"/>
<path
d="M137.100603,214.337781 L132.985012,216.496176 L132.727788,261.283018 L140.44452,277.471014 L154.84908,272.07502 L154.334634,261.283018 L138.643949,245.904397 L137.100603,214.337781 Z"
fill="#76C39B"/>
<path
d="M208.129058,182.777915 L184.207188,189.465751 L158.227536,211.58704 L144.080189,211.072594 L184.978864,183.549591 L208.129058,182.777915 Z"
fill="#000000"/>
<path
d="M198.296885,185.732856 L190.800333,171.941063 L123.331329,166.659097 L153.585284,179.277122 L198.296885,185.732856 Z"
fill="#73B08F"/>
<path
d="M158.964673,210.730612 L162.308591,218.6168 L169.510872,223.783638 L170.796993,223.783638 L170.282547,232.213718 L148.161259,241.459608 L140.701745,224.327502 L138.643949,214.265786 L145.331786,208.827031 L158.964673,210.730612 Z"
fill="#73B08F"/>
<path
d="M170.796993,255.092495 L157.421336,249.176335 L152.276838,259.722534 L154.077419,262.294776 L153.820189,273.61265 L163.594712,284.673295 L162.051375,263.838127 L164.366387,262.809221 L172.083114,270.268732 L168.481981,261.78033 L160.250794,257.407521 L162.051375,254.320819 L170.796993,258.179183 L170.796993,255.092495 Z"
fill="#000000"/>
<path
d="M174.140911,211.107118 L168.996426,223.453898 L170.025332,234.000097 L180.571531,247.118538 L179.28541,226.02614 L180.828746,224.997249 L189.059933,232.19953 L185.716015,224.225574 L176.455937,219.338305 L177.999274,216.766063 L187.516582,220.881656 L187.259352,217.280508 L174.140911,211.107118 Z"
fill="#000000"/>
<path
d="M221.727421,156.575557 L193.175526,149.887722 L193.175526,176.639064 L196.51943,182.555224 L221.727421,182.812439 L245.649291,191.043626 L244.877615,187.699708 L230.215837,179.211306 L200.892253,167.636202 L217.611842,167.378986 L201.406699,159.919475 L221.727421,156.575557 Z"
fill="#C5E4D3"/>
<path
d="M255.030218,181.029135 L244.226789,163.53787 L219.533243,156.078359 L199.984197,159.679507 L215.41765,167.396234 L201.013088,167.910679 L228.021646,179.485784 L243.455113,186.945295 L255.030218,181.029135 Z"
fill="#73B08F"/>
<path
d="M240.95192,201.84704 L245.067513,195.673665 L241.980825,187.699708 L254.842036,180.497427 L255.099266,195.159219 L247.382539,202.10427 L240.95192,201.84704 Z"
fill="#000000"/>
<path
d="M251.264829,178.835515 L242.436634,174.715703 L237.728265,178.835515"
stroke="#000000" stroke-width="0.30000001"/>
</g>
</svg>
{% endmacro %}
{% macro eventsIcon() %}
<svg xmlns:xlink="http://www.w3.org/1999/xlink"
fill="#ffffff" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
<defs>
<path d="M0 0h24v24H0V0z" id="a"/>
</defs>
<clipPath id="b">
<use overflow="visible" xlink:href="#a"/>
</clipPath>
<path clip-path="url(#b)"
d="M23 8c0 1.1-.9 2-2 2-.18 0-.35-.02-.51-.07l-3.56 3.55c.05.16.07.34.07.52 0 1.1-.9 2-2 2s-2-.9-2-2c0-.18.02-.36.07-.52l-2.55-2.55c-.16.05-.34.07-.52.07s-.36-.02-.52-.07l-4.55 4.56c.05.16.07.33.07.51 0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2c.18 0 .35.02.51.07l4.56-4.55C8.02 9.36 8 9.18 8 9c0-1.1.9-2 2-2s2 .9 2 2c0 .18-.02.36-.07.52l2.55 2.55c.16-.05.34-.07.52-.07s.36.02.52.07l3.55-3.56C19.02 8.35 19 8.18 19 8c0-1.1.9-2 2-2s2 .9 2 2z"/>
</svg>
{% endmacro %}
{% macro dbIcon() %}
<svg xmlns="http://www.w3.org/2000/svg"
fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24">
<path d="M0 0h24v24H0z" fill="none"/>
<path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/>
</svg>
{% endmacro %}
{% macro viewIcon() %}
<svg xmlns="http://www.w3.org/2000/svg"
fill="#FFFFFF" height="24" viewBox="0 0 24 24" width="24">
<path
d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-5 14H4v-4h11v4zm0-5H4V9h11v4zm5 5h-4V9h4v9z"/>
<path d="M0 0h24v24H0z" fill="none"/>
</svg>
{% endmacro %}
{% macro bugIcon() %}
<svg xmlns="http://www.w3.org/2000/svg"
fill="#ffffff" height="24" viewBox="0 0 24 24" width="24">
<path d="M0 0h24v24H0z" fill="none"/>
<path
d="M20 8h-2.81c-.45-.78-1.07-1.45-1.82-1.96L17 4.41 15.59 3l-2.17 2.17C12.96 5.06 12.49 5 12 5c-.49 0-.96.06-1.41.17L8.41 3 7 4.41l1.62 1.63C7.88 6.55 7.26 7.22 6.81 8H4v2h2.09c-.05.33-.09.66-.09 1v1H4v2h2v1c0 .34.04.67.09 1H4v2h2.81c1.04 1.79 2.97 3 5.19 3s4.15-1.21 5.19-3H20v-2h-2.09c.05-.33.09-.66.09-1v-1h2v-2h-2v-1c0-.34-.04-.67-.09-1H20V8zm-6 8h-4v-2h4v2zm0-4h-4v-2h4v2z"/>
</svg>
{% endmacro %}
{% macro zendIcon() %}
<svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#"
xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg"
xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" height="24px" width="20px" version="1.1" id="svg2"
inkscape:version="0.48.4 r9939" sodipodi:docname="Zend_Technologies_Logo.svg" viewBox="0 0 54 66.489998">
<title id="title3009">Zend logo</title>
<sodipodi:namedview xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
          xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" pagecolor="#ffffff"
          bordercolor="#666666" borderopacity="1" objecttolerance="10" gridtolerance="10"
          guidetolerance="10" inkscape:pageopacity="0" inkscape:pageshadow="2"
          inkscape:window-width="1600" inkscape:window-height="844" id="namedview26" showgrid="false"
          inkscape:zoom="1" inkscape:cx="45.319996" inkscape:cy="33.743149" inkscape:window-x="-4"
          inkscape:window-y="-4" inkscape:window-maximized="1" inkscape:current-layer="svg2"/>
<defs id="defs4"/>
<metadata id="metadata6">
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
<cc:Work xmlns:cc="http://creativecommons.org/ns#" rdf:about="">
<dc:format xmlns:dc="http://purl.org/dc/elements/1.1/">image/svg+xml</dc:format>
<dc:type xmlns:dc="http://purl.org/dc/elements/1.1/" rdf:resource="http://purl.org/dc/dcmitype/StillImage"/>
<dc:title xmlns:dc="http://purl.org/dc/elements/1.1/">Zend logo</dc:title>
</cc:Work>
</rdf:RDF>
</metadata>
<g transform="translate(-348.15625,-305.32964)" id="g8">
<path xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
d="m 401.25,305.625 -32,42.1875 24.7812,0 c 3.9925,0 7.21875,-3.265 7.21875,-7.25 V 305.625 z m -44.875,6.96875 c -3.98875,0 -7.21875,3.26125 -7.21875,7.25 v 34.9688 l 32,-42.2188 h -24.7812 z"
id="path10" inkscape:connector-curvature="0" style="fill:#01719f;fill-rule:nonzero"/>
<path xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
d="m 361.264,370.413 -1.395,-1.77875 -6.50375,0 c 0.1225,-0.165 7.88875,-10.39 7.88875,-10.39 h -11.4525 l 1.37625,1.7575 h 6.52625 c -0.1225,0.16 -7.92375,10.4075 -7.92375,10.4075 l 11.4838,0.004 z"
id="path14" inkscape:connector-curvature="0" style="fill:#01719f;fill-rule:nonzero"/>
<path xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
d="m 381.415,358.244 -5.30375,0 0,12.13 1.8125,0 0,-10.32 3.235,0 c 3.18125,0 5.08125,1.45125 5.08125,3.88625 v 6.43375 h 1.80875 v -6.52625 c 0,-2.78875 -2.05125,-5.60375 -6.63375,-5.60375"
id="path16" inkscape:connector-curvature="0" style="fill:#01719f;fill-rule:nonzero"/>
<path xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
d="m 399.7,354.782 c 0,0 -10e-4,3.3625 -10e-4,3.46125 h -4.18625 c -3.05625,0 -5.455,2.00875 -5.96625,5.00125 -0.0475,0.38875 -0.0738,0.76125 -0.0738,1.06 0,0.435 0.0513,0.86625 0.0738,1.03125 0.50125,2.94625 3.01,5.07375 5.96625,5.07375 l 5.78125,0.004 v -16.8388 l -1.59375,1.2075 z m -0.21875,5.27125 0,8.5525 -4.0025,0 c -1.88125,0 -3.655,-1.45375 -4.1225,-3.37875 0,-0.002 -0.0713,-0.9225 -0.0713,-0.9225 0,-0.25125 0.025,-0.5825 0.0713,-0.92875 0.43125,-1.9875 2.1425,-3.31 4.2675,-3.31 0,0 3.755,-0.0125 3.8575,-0.0125"
id="path18" inkscape:connector-curvature="0" style="fill:#01719f;fill-rule:nonzero"/>
<path xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
d="m 374.266,364.98 0.0312,-0.665 c 0,-3.59125 -2.91875,-6.51125 -6.5075,-6.51125 -3.58875,0 -6.50625,2.92 -6.50625,6.50875 0,3.5875 2.9175,6.5075 6.50625,6.5075 2.7325,0 5.19,-1.72875 6.11875,-4.30625 h -1.935 c -0.82125,1.565 -2.415,2.535 -4.18375,2.535 -2.35375,0 -4.34875,-1.75 -4.6775,-4.07625 h 9.48375 l 1.67,0.007 z m -11.055,-1.82 c 0.5225,-2.07875 2.43625,-3.5825 4.57875,-3.5825 2.145,0 4.0575,1.50375 4.5825,3.5825 H 363.211 z"
id="path20" inkscape:connector-curvature="0" style="fill:#01719f;fill-rule:nonzero"/>
</g>
</svg>
{% endmacro %}
{% macro phpIcon() %}
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="18px"
viewBox="0 0 256 134" version="1.1" preserveAspectRatio="xMinYMin meet">
<g fill-rule="evenodd">
<ellipse fill="#8993BE" cx="128" cy="66.630137" rx="128" ry="66.630137"/>
<path
d="M35.9452055,106.082192 L49.9726027,35.0684932 L82.4109589,35.0684932 C96.4383562,35.9452055 103.452055,42.9589041 103.452055,55.2328767 C103.452055,76.2739726 86.7945205,88.5479452 71.890411,87.6712329 L56.109589,87.6712329 L52.6027397,106.082192 L35.9452055,106.082192 L35.9452055,106.082192 Z M59.6164384,74.5205479 L64,48.2191781 L75.3972603,48.2191781 C81.5342466,48.2191781 85.9178082,50.8493151 85.9178082,56.109589 C85.0410959,71.0136986 78.0273973,73.6438356 70.1369863,74.5205479 L59.6164384,74.5205479 L59.6164384,74.5205479 Z"
fill="#232531"/>
<path
d="M100.191781,87.6712329 L114.219178,16.6575342 L130.876712,16.6575342 L127.369863,35.0684932 L143.150685,35.0684932 C157.178082,35.9452055 162.438356,42.9589041 160.684932,51.7260274 L154.547945,87.6712329 L137.013699,87.6712329 L143.150685,55.2328767 C144.027397,50.8493151 144.027397,48.2191781 137.890411,48.2191781 L124.739726,48.2191781 L116.849315,87.6712329 L100.191781,87.6712329 L100.191781,87.6712329 Z"
fill="#232531"/>
<path
d="M153.424658,106.082192 L167.452055,35.0684932 L199.890411,35.0684932 C213.917808,35.9452055 220.931507,42.9589041 220.931507,55.2328767 C220.931507,76.2739726 204.273973,88.5479452 189.369863,87.6712329 L173.589041,87.6712329 L170.082192,106.082192 L153.424658,106.082192 L153.424658,106.082192 Z M177.09589,74.5205479 L181.479452,48.2191781 L192.876712,48.2191781 C199.013699,48.2191781 203.39726,50.8493151 203.39726,56.109589 C202.520548,71.0136986 195.506849,73.6438356 187.616438,74.5205479 L177.09589,74.5205479 L177.09589,74.5205479 Z"
fill="#232531"/>
</g>
</svg>
{% endmacro %}
<div id="nuc-debug-bar">
  <div class="nuc-debug-bar-nav">
    <div class="nuc-debug-bar-nav-wrapper">
      <ul class="left">
        <li class="http-{{ responseHttpCode }}">
          <span>
            {{ responseHttpCode }}
          </span>
        </li>
        <li>
          <table style="margin: 0;padding: 0;white-space: nowrap;" class="dropup-content bordered">
            <tbody>
            <tr>
              <td>HTTP status</td>
              <td>{{ responseHttpCode }}</td>
            </tr>
            <tr>
              <td>Module</td>
              <td>{{ dispatch['module'] }}</td>
            </tr>
            <tr>
              <td>Controller</td>
              <td>{{ dispatch['controller'] }}@{{ dispatch['method'] }}</td>
            </tr>
            <tr>
              <td>Controller Class</td>
              <td>{{ dispatch['controllerClass'] }}</td>
            </tr>
            <tr>
              <td>Route</td>
              <td>{{ route['pattern'] }}</td>
            </tr>
            </tbody>
          </table>
          <span>
            {{ dispatch['controller'] }}@{{ dispatch['method'] }}
          </span>
        </li>
        <li class="{{ render_time > 0.6 ? 'slow-request' : '' }}">
          <span>
            {{ render_time | human_mtime }}
          </span>
        </li>
        <li>
          <span>
            {{ mem_peak | human_bytes }}
          </span>
        </li>
        <li class="{{ php_errors | length > 0 ? 'with-errors' : 'no-errors' }}">
          <div class="dropup-content bottom-sheet">
            <table style="margin: 0;padding: 0;white-space: nowrap;" class="bordered">
              <tbody>
              {% for error in php_errors %}
                <tr>
                  <td>{{ error['typeStr'] }}</td>
                  <td>{{ error['message'] }}</td>
                </tr>
              {% endfor %}
              </tbody>
            </table>
          </div>
          <span>
            {{ bugIcon() }}
            <span class="bag">{{ php_errors | length }}</span>
          </span>
        </li>
        <li class="">
          <table style="margin: 0;padding: 0;white-space: nowrap;" class="dropup-content bordered">
            <tbody>
            <tr>
              <td>Views</td>
              <td>{{ viewProfiles['renderViews'] | default([]) | length }}</td>
            </tr>
            <tr>
              <td>View not found</td>
              <td>{{ viewProfiles['notFoundView'] | default([]) | length }}</td>
            </tr>
            <tr>
              <td>Render</td>
              <td>{{ viewProfiles['render']['elapsedTime'] | human_mtime }}</td>
            </tr>
            </tbody>
          </table>
          <span>
            {{ viewIcon() }}
            <span class="info">{{ viewProfiles['render']['elapsedTime'] | human_mtime }}</span>
          </span>
        </li>
        <li class="">
          {% if dbProfiles is not empty %}
          <div class="dropup-content bottom-sheet">
            <table style="margin: 0;padding: 0;" class="bordered">
              <thead>
              <tr>
                <th>-</th>
                <th>sql</th>
                <th>vars</th>
              </tr>
              </thead>
              <tbody>
              {% for profile in dbProfiles | default([]) %}
                <tr>
                  <td>
                    <small style="white-space: nowrap;">{{ profile.getTotalElapsedSeconds() | human_mtime }}</small>
                  </td>
                  <td>
                    <pre class="sql">{{ profile.getSqlStatement() | sql_highlight }}</pre>
                  </td>
                  <td style="padding: 5px 10px;border-radius: 0">
                    {% set vars = profile.getSqlVariables() %}
                    {% if vars is not null %}
                      {% for var, value in vars %}
                        <pre>:{{ var }} = {{ value }}</pre>
                      {% endfor %}
                    {% else %}
                      --
                    {% endif %}
                  </td>
                </tr>
              {% endfor %}
              </tbody>
            </table>
          </div>
          {% endif %}
          <span>
            {{ dbIcon() }}
            <span class="info">{{ dbProfiles | default([]) |  length }}
              {% if dbProfiles is not empty %}
              {% set dbTotalTime = 0 %}
              {% for profile in dbProfiles | default([]) %}
                {% set dbTotalTime = dbTotalTime + profile.getTotalElapsedSeconds() %}
              {% endfor %} in {{ dbTotalTime | human_mtime }}
              {% endif %}
            </span>
          </span>
        </li>
        <li class="">
          <div class="dropup-content bottom-sheet">
            <table style="margin: 0;padding: 0;" class="bordered">
              {% set mt_start = _SERVER['REQUEST_TIME_FLOAT'] %}
              <thead>
              <tr>
                <th>-</th>
                <th>type</th>
                <th>src</th>
                <th>data</th>
              </tr>
              </thead>
              <tbody>
              <tr>
                <td>
                  <small>0 ns</small>
                </td>
                <td>
                  <small class="event">
                    REQUEST_TIME_FLOAT
                  </small>
                </td>
                <td>
                </td>
                <td>
                </td>
              </tr>
              {% for event in events | default([]) %}
                <tr>
                  <td style="white-space:nowrap">
                    <small>{{ (event['mt'] - mt_start) | human_mtime }}</small>
                  </td>
                  <td style="white-space:nowrap">
                    <small class="event">
                      <span class="space">{{ event['space'] }}</span>:<span class="type">{{ event['type'] }}</span>
                    </small>
                  </td>
                  <td>
                    <small>{{ event['src'] }}</small>
                  </td>
                  <td style="word-break: break-all;">
                    <small title="{{ is_string(event['raw_data']) ? event['raw_data'] : '' }}">{{ event['data'] }}</small>
                  </td>
                </tr>
              {% endfor %}
              </tbody>
            </table>
          </div>
          <span>
            {{ eventsIcon() }}
            <span class="bag" data-badge-caption>{{ events | length }}</span>
          </span>
        </li>
        {% for name, profiler in profilers | default([]) %}
          {% set profiles = profiler.getProfiles() | default([]) %}
          <li class="">
            {% if profiles is not empty %}
          <div class="dropup-content bottom-sheet">
            <table style="margin: 0;padding: 0;" class="bordered">
              <thead>
              <tr>
                <th>-</th>
                <th>sql</th>
                <th>vars</th>
              </tr>
              </thead>
              <tbody>
              {% for profile in profiles %}
                <tr>
                  <td>
                    <small style="white-space: nowrap;">{{ profile.getTotalElapsedSeconds() | human_mtime }}</small>
                  </td>
                  <td>
                    <pre>{{ profile.getSqlStatement() }}</pre>
                  </td>
                  <td style="padding: 5px 10px;border-radius: 0">
                    {% set vars = profile.getSqlVariables() %}
                    {% if vars is not null %}
                      {% for var, value in vars %}
                        <pre>:{{ var }} = {{ value }}</pre>
                      {% endfor %}
                    {% else %}
                      --
                    {% endif %}
                  </td>
                </tr>
              {% endfor %}
              </tbody>
            </table>
          </div>
          {% endif %}
          <span>
            {{ name }}
            <span class="info">{{ profiles |  length }}
              {% if profiles is not empty %}
                {% set dbTotalTime = 0 %}
                {% for profile in profiles %}
                  {% set dbTotalTime = dbTotalTime + profile.getTotalElapsedSeconds() %}
                {% endfor %} in {{ dbTotalTime | human_mtime }}
              {% endif %}
            </span>
          </span>
          </li>
        {% endfor %}
      </ul>
      <ul class="right">
        <li>
          <table class="dropup-content">
            <tr>
              <td style="padding:5px 0 0 5px">{{ neutrinoIcon() }}</td>
              <td style="word-break: keep-all;white-space: nowrap;padding: 5px;">{{ build['neutrino']['version'] }}</td>
            </tr>
            <tr>
              <td style="padding:5px 0 0 5px">{{ phalconIcon() }}</td>
              <td style="word-break: keep-all;white-space: nowrap;padding: 5px;">{{ build['phalcon']['version'] }}</td>
            </tr>
            <tr>
              <td colspan="2">
                <a href="#debug-build-info" data-debug-modal-trigger="debug-build-info" class="debug-modal-trigger">more info</a>
              </td>
            </tr>
          </table>
          <span>
            {{ neutrinoIcon() }}
            {{ build['neutrino']['version'] }}
          </span>
        </li>
      </ul>
    </div>
  </div>
</div>
<div id="nuc-debug-modal" class="nuc-debug-modal-wrapper">
  <div class="debug-modal" id="debug-build-info">
    <div class="debug-modal-content">
      <ul>
        <li>
          <span>{{ neutrinoIcon() }} neutrino</span>
          <code>{{ build['neutrino']['version'] }}</code>
          {% if build['neutrino']['const'] is not empty %}
          <div>
            {% for const, value in build['neutrino']['const'] %}
              <p>
                <span>{{ const }}</span>
                <code>{{ value }}</code>
              </p>
            {% endfor %}
          </div>
          {% endif %}
        </li>
        <li>
          <span>{{ phalconIcon() }} phalcon</span>
          <code>{{ build['phalcon']['version'] }}</code>
          {% if build['phalcon']['ini'] is not empty %}
          <div>
            {% for key, info in build['phalcon']['ini'] %}
              <p>
                <span>{{ key }}</span>
                <code>{{ info['global_value'] }}</code>
                <code>{{ info['local_value'] }}</code>
              </p>
            {% endfor %}
          </div>
          {% endif %}
        </li>
        <li>
          <span>{{ zendIcon() }} zend</span>
          <code>{{ build['zend']['version'] }}</code>
          {% if build['zend']['extensions'] is not empty %}
            <div>
            {% for key, version in build['zend']['extensions'] %}
              <p>
                <span>{{ key }}</span>
                <span>{{ version }}</span>
              </p>
            {% endfor %}
            </div>
          {% endif %}
        </li>
        <li>
          <span>{{ phpIcon() }}php</span>
          <code>{{ build['php']['version'] }}</code>
          {% if build['php']['extensions'] is not empty %}
          <div>
            {% for key, version in build['php']['extensions'] %}
              <p>
                <span>{{ key }}</span>
                <span>{{ version }}</span>
              </p>
            {% endfor %}
          </div>
          {% endif %}
        </li>
      </ul>
    </div>
  </div>
</div>
<script>
  (function(h,a){function d(b,a){if(Array.isArray(b))for(var c=0,d=b.length;c<d;c++)a.call(b[c],c,b[c]);else for(c in b)e.call(b,c)&&a.call(b[c],c,b[c])}function f(b){a.body.classList.add("nuc-debug-modal-open");d(a.querySelectorAll("#nuc-debug-modal .debug-modal"),function(){this.classList.remove("open")});a.getElementById(b).classList.add("open")}function g(){a.body.classList.remove("nuc-debug-modal-open");d(a.querySelectorAll("#nuc-debug-modal .debug-modal"),function(){this.classList.remove("open")})}
    var e=Object.prototype.hasOwnProperty;(function(){d(a.querySelectorAll("#nuc-debug-bar a.debug-modal-trigger"),function(){this.addEventListener("click",function(){f(this.getAttribute("data-debug-modal-trigger"))})})})();(function(){a.getElementById("nuc-debug-modal").addEventListener("click",function(b){b.target===this&&g()})})();d(a.querySelectorAll("#nuc-debug-modal ul li"),function(){this.addEventListener("click",function(){d(a.querySelectorAll("#nuc-debug-modal ul li"),function(b,a){a!==this&&
    a.classList.remove("open")}.bind(this));this.classList.toggle("open")})})})(window,document);
</script>
{#
// SASS
$materialize-red: (
  "base": #e51c23,
  "lighten-5": #fdeaeb,
  "lighten-4": #f8c1c3,
  "lighten-3": #f3989b,
  "lighten-2": #ee6e73,
  "lighten-1": #ea454b,
  "darken-1": #d0181e,
  "darken-2": #b9151b,
  "darken-3": #a21318,
  "darken-4": #8b1014,
);
$red: (
  "base": #f44336,
  "lighten-5": #ffebee,
  "lighten-4": #ffcdd2,
  "lighten-3": #ef9a9a,
  "lighten-2": #e57373,
  "lighten-1": #ef5350,
  "darken-1": #e53935,
  "darken-2": #d32f2f,
  "darken-3": #c62828,
  "darken-4": #b71c1c,
  "accent-1": #ff8a80,
  "accent-2": #ff5252,
  "accent-3": #ff1744,
  "accent-4": #d50000
);
$pink: (
  "base": #e91e63,
  "lighten-5": #fce4ec,
  "lighten-4": #f8bbd0,
  "lighten-3": #f48fb1,
  "lighten-2": #f06292,
  "lighten-1": #ec407a,
  "darken-1": #d81b60,
  "darken-2": #c2185b,
  "darken-3": #ad1457,
  "darken-4": #880e4f,
  "accent-1": #ff80ab,
  "accent-2": #ff4081,
  "accent-3": #f50057,
  "accent-4": #c51162
);
$purple: (
  "base": #9c27b0,
  "lighten-5": #f3e5f5,
  "lighten-4": #e1bee7,
  "lighten-3": #ce93d8,
  "lighten-2": #ba68c8,
  "lighten-1": #ab47bc,
  "darken-1": #8e24aa,
  "darken-2": #7b1fa2,
  "darken-3": #6a1b9a,
  "darken-4": #4a148c,
  "accent-1": #ea80fc,
  "accent-2": #e040fb,
  "accent-3": #d500f9,
  "accent-4": #a0f
);
$deep-purple: (
  "base": #673ab7,
  "lighten-5": #ede7f6,
  "lighten-4": #d1c4e9,
  "lighten-3": #b39ddb,
  "lighten-2": #9575cd,
  "lighten-1": #7e57c2,
  "darken-1": #5e35b1,
  "darken-2": #512da8,
  "darken-3": #4527a0,
  "darken-4": #311b92,
  "accent-1": #b388ff,
  "accent-2": #7c4dff,
  "accent-3": #651fff,
  "accent-4": #6200ea
);
$indigo: (
  "base": #3f51b5,
  "lighten-5": #e8eaf6,
  "lighten-4": #c5cae9,
  "lighten-3": #9fa8da,
  "lighten-2": #7986cb,
  "lighten-1": #5c6bc0,
  "darken-1": #3949ab,
  "darken-2": #303f9f,
  "darken-3": #283593,
  "darken-4": #1a237e,
  "accent-1": #8c9eff,
  "accent-2": #536dfe,
  "accent-3": #3d5afe,
  "accent-4": #304ffe
);
$blue: (
  "base": #2196f3,
  "lighten-5": #e3f2fd,
  "lighten-4": #bbdefb,
  "lighten-3": #90caf9,
  "lighten-2": #64b5f6,
  "lighten-1": #42a5f5,
  "darken-1": #1e88e5,
  "darken-2": #1976d2,
  "darken-3": #1565c0,
  "darken-4": #0d47a1,
  "accent-1": #82b1ff,
  "accent-2": #448aff,
  "accent-3": #2979ff,
  "accent-4": #2962ff
);
$light-blue: (
  "base": #03a9f4,
  "lighten-5": #e1f5fe,
  "lighten-4": #b3e5fc,
  "lighten-3": #81d4fa,
  "lighten-2": #4fc3f7,
  "lighten-1": #29b6f6,
  "darken-1": #039be5,
  "darken-2": #0288d1,
  "darken-3": #0277bd,
  "darken-4": #01579b,
  "accent-1": #80d8ff,
  "accent-2": #40c4ff,
  "accent-3": #00b0ff,
  "accent-4": #0091ea
);
$cyan: (
  "base": #00bcd4,
  "lighten-5": #e0f7fa,
  "lighten-4": #b2ebf2,
  "lighten-3": #80deea,
  "lighten-2": #4dd0e1,
  "lighten-1": #26c6da,
  "darken-1": #00acc1,
  "darken-2": #0097a7,
  "darken-3": #00838f,
  "darken-4": #006064,
  "accent-1": #84ffff,
  "accent-2": #18ffff,
  "accent-3": #00e5ff,
  "accent-4": #00b8d4
);
$teal: (
  "base": #009688,
  "lighten-5": #e0f2f1,
  "lighten-4": #b2dfdb,
  "lighten-3": #80cbc4,
  "lighten-2": #4db6ac,
  "lighten-1": #26a69a,
  "darken-1": #00897b,
  "darken-2": #00796b,
  "darken-3": #00695c,
  "darken-4": #004d40,
  "accent-1": #a7ffeb,
  "accent-2": #64ffda,
  "accent-3": #1de9b6,
  "accent-4": #00bfa5
);
$green: (
  "base": #4caf50,
  "lighten-5": #e8f5e9,
  "lighten-4": #c8e6c9,
  "lighten-3": #a5d6a7,
  "lighten-2": #81c784,
  "lighten-1": #66bb6a,
  "darken-1": #43a047,
  "darken-2": #388e3c,
  "darken-3": #2e7d32,
  "darken-4": #1b5e20,
  "accent-1": #b9f6ca,
  "accent-2": #69f0ae,
  "accent-3": #00e676,
  "accent-4": #00c853
);
$light-green: (
  "base": #8bc34a,
  "lighten-5": #f1f8e9,
  "lighten-4": #dcedc8,
  "lighten-3": #c5e1a5,
  "lighten-2": #aed581,
  "lighten-1": #9ccc65,
  "darken-1": #7cb342,
  "darken-2": #689f38,
  "darken-3": #558b2f,
  "darken-4": #33691e,
  "accent-1": #ccff90,
  "accent-2": #b2ff59,
  "accent-3": #76ff03,
  "accent-4": #64dd17
);
$lime: (
  "base": #cddc39,
  "lighten-5": #f9fbe7,
  "lighten-4": #f0f4c3,
  "lighten-3": #e6ee9c,
  "lighten-2": #dce775,
  "lighten-1": #d4e157,
  "darken-1": #c0ca33,
  "darken-2": #afb42b,
  "darken-3": #9e9d24,
  "darken-4": #827717,
  "accent-1": #f4ff81,
  "accent-2": #eeff41,
  "accent-3": #c6ff00,
  "accent-4": #aeea00
);
$yellow: (
  "base": #ffeb3b,
  "lighten-5": #fffde7,
  "lighten-4": #fff9c4,
  "lighten-3": #fff59d,
  "lighten-2": #fff176,
  "lighten-1": #ffee58,
  "darken-1": #fdd835,
  "darken-2": #fbc02d,
  "darken-3": #f9a825,
  "darken-4": #f57f17,
  "accent-1": #ffff8d,
  "accent-2": #ff0,
  "accent-3": #ffea00,
  "accent-4": #ffd600
);
$amber: (
  "base": #ffc107,
  "lighten-5": #fff8e1,
  "lighten-4": #ffecb3,
  "lighten-3": #ffe082,
  "lighten-2": #ffd54f,
  "lighten-1": #ffca28,
  "darken-1": #ffb300,
  "darken-2": #ffa000,
  "darken-3": #ff8f00,
  "darken-4": #ff6f00,
  "accent-1": #ffe57f,
  "accent-2": #ffd740,
  "accent-3": #ffc400,
  "accent-4": #ffab00
);
$orange: (
  "base": #ff9800,
  "lighten-5": #fff3e0,
  "lighten-4": #ffe0b2,
  "lighten-3": #ffcc80,
  "lighten-2": #ffb74d,
  "lighten-1": #ffa726,
  "darken-1": #fb8c00,
  "darken-2": #f57c00,
  "darken-3": #ef6c00,
  "darken-4": #e65100,
  "accent-1": #ffd180,
  "accent-2": #ffab40,
  "accent-3": #ff9100,
  "accent-4": #ff6d00
);
$deep-orange: (
  "base": #ff5722,
  "lighten-5": #fbe9e7,
  "lighten-4": #ffccbc,
  "lighten-3": #ffab91,
  "lighten-2": #ff8a65,
  "lighten-1": #ff7043,
  "darken-1": #f4511e,
  "darken-2": #e64a19,
  "darken-3": #d84315,
  "darken-4": #bf360c,
  "accent-1": #ff9e80,
  "accent-2": #ff6e40,
  "accent-3": #ff3d00,
  "accent-4": #dd2c00
);
$brown: (
  "base": #795548,
  "lighten-5": #efebe9,
  "lighten-4": #d7ccc8,
  "lighten-3": #bcaaa4,
  "lighten-2": #a1887f,
  "lighten-1": #8d6e63,
  "darken-1": #6d4c41,
  "darken-2": #5d4037,
  "darken-3": #4e342e,
  "darken-4": #3e2723
);
$blue-grey: (
  "base": #607d8b,
  "lighten-5": #eceff1,
  "lighten-4": #cfd8dc,
  "lighten-3": #b0bec5,
  "lighten-2": #90a4ae,
  "lighten-1": #78909c,
  "darken-1": #546e7a,
  "darken-2": #455a64,
  "darken-3": #37474f,
  "darken-4": #263238
);
$grey: (
  "base": #9e9e9e,
  "lighten-5": #fafafa,
  "lighten-4": #f5f5f5,
  "lighten-3": #eee,
  "lighten-2": #e0e0e0,
  "lighten-1": #bdbdbd,
  "darken-1": #757575,
  "darken-2": #616161,
  "darken-3": #424242,
  "darken-4": #212121
);
$shades: (
  "black": #000,
  "white": #fff,
  "transparent": transparent
);

#nuc-debug-bar {
  position: relative;
  z-index: 99999;
  -webkit-font-smoothing: subpixel-antialiased;
  -moz-osx-font-smoothing: auto;
  line-height: 1.5;
  font-weight: 300;
  font-family: Arial, sans-serif;
  font-size: 15px;

  [class^=http-1], [class^=http-2] {
    background-color: map-get($green, lighten-1);
  }
  [class^=http-3] {
    background-color: map-get($yellow, darken-2);
  }
  [class^=http-4] {
    background-color: map-get($deep-orange, darken-1);
  }
  [class^=http-5] {
    background-color: map-get($red, darken-3);
  }
  small {
    font-size: 75%;
  }
  table, tr, td {
    font-size: 13px;
    border: none;
  }
  table {
    width: 100%;
    display: table;
    border-collapse: collapse;
    border-spacing: 0;
    &.bordered > thead > tr {
      font-size: 13px;
      border-bottom: 1px solid #8a8a8a;
    }
    &.bordered > tbody > tr {
      border-bottom: 1px groove #8a8a8a;
    }

    tr {
      background-color: map-get($grey, darken-4) !important;
      th, td {
        padding: 5px 10px;
        border-radius: 0;
        display: table-cell;
        text-align: left;
        vertical-align: middle;
        small {
          font-size: 11px;
        }
      }
    }
  }
  .collection {
    margin: 0;
    padding: 0
  }
  pre {
    white-space: pre-line;
    word-break: break-all;
    font-size: 12px !important;
    margin: 0;
    line-height: 1.4;
  }
  .nuc-debug-bar-nav {
    background-color: #212121;
    color: #fafafa;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    height: 35px;
    line-height: 35px;

    ul {
      margin: 0;
      padding: 0;
      list-style-type: none;
      li {
        list-style-type: none;
        transition: background-color .3s;
        float: left;
        padding: 0;
        position: relative;

        &.active {
          background-color: rgba(0, 0, 0, .1);
        }

        > span, > a, > small {
          display: block;
          padding: 0 7px;
          font-size: 13px;
          height: 35px;
        }

        .dropup-content {
          display: none;
        }

        &:hover > .dropup-content {
          line-height: 1.5;
          display: inherit;
          position: absolute;
          bottom: 35px;
          &.bottom-sheet {
            position: fixed;
            left: 0;
            right: 0;
            overflow: auto;
            max-height: calc(100vh / 2.5);
          }
        }
        > a, > span {
          transition: background-color .3s;
          color: #fafafa;
          display: block;
          cursor: pointer;
          text-decoration: none;
          i, svg {
            position: relative;
            top: 6px;
          }
          .bag {
            font-weight: 300;
            font-size: 0.8rem;
            color: #fff;
            background-color: #26a69a;
            border-radius: 2px;;
            min-width: 1rem;
            padding: 0 8px;
            margin-top: 7px;
            margin-left: 3px;
            text-align: center;
            line-height: 22px;
            height: 22px;
            float: right;
            box-sizing: border-box;
          }
          &:hover {
            background-color: rgba(0, 0, 0, .1);
          }
        }
      }

      &.left {
        float: left;
      }
      &.right {
        float: right;
      }
    }
  }

  .sql {
    .string {
      color: map-get($green, lighten-3) !important;
    }
    .table {
      color: map-get($blue, lighten-3) !important;
    }
    .column {
      color: map-get($purple, lighten-3) !important;
    }
    .func {
      color: map-get($yellow, darken-1) !important;
    }
    .keyw {
      color: map-get($orange, darken-1) !important;
    }
  }

  .event {
    .space {
      color: map-get($blue, lighten-3) !important;
    }
    .type {
      color: map-get($purple, lighten-3) !important;
    }
  }

  .slow-request {
    background: map-get($amber, accent-4);
  }

  .no-errors {
    .bag {
      background-color: map-get($green, lighten-2) !important;
    }
  }
  .with-errors {
    background-color: map-get($deep-orange, darken-1) !important;
    .bag {
      background-color: map-get($red, darken-4) !important;
    }
  }
}

#nuc-debug-modal {
  &.nuc-debug-modal-wrapper {
    display: none;
    position: fixed;
    top: 0;
    right: 0;
    left: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.6);
    overflow: auto;
    z-index: 9999999;
    font-size: 14px;
    font-weight: normal;
    font-family: Arial, sans-serif;
    line-height: 1.5;

    *, *:before, *:after {
      box-sizing: inherit;
    }
  }
  .debug-modal {
    display: none;
    margin-left: auto;
    margin-right: auto;
    margin-top: 20px;
    max-width: 1280px;
    width: 90%;
    background-color: map-get($grey, darken-4);
    padding: 15px;
    color: map-get($grey, lighten-3);
  }

  @media only screen and (min-width: 601px) {
    .debug-modal {
      width: 85%
    }
  }
  @media only screen and (min-width: 993px) {
    .debug-modal {
      width: 70%
    }
  }

  #debug-build-info {
    ul {
      margin: 0;
      padding: 0;
      list-style-type: none;

      li {
        color: map-get($grey, lighten-5) !important;
        list-style-type: none;
        padding: 15px 10px;
        background: rgba(255,255,255,0.1);
        cursor: pointer;
        box-sizing: inherit;

        &:not(:last-child){
          margin-bottom: 10px;
        }

        span, code {
          display: inline-block;
          text-align: left;
        }
        span {
          width: 20%;
          position: relative;
          i, svg{
            position: relative;
            top: 6px;
          }
        }

        > div {
          display: none;
          padding: 15px 0 0 15px;

          > p {
            display: table-row;

            > span, > code {
              display: table-cell;
              width: auto;
              padding: 2px 5px;
            }
            > span {
              white-space: nowrap;
              word-break: keep-all;
            }
            > code {
              word-break: break-all;
            }
          }
        }

        &.open {
          > div {
            display: table;
          }
        }
      }
    }
  }
}
body {
  padding-bottom: 35px !important;
  &.nuc-debug-modal-open {
    overflow: hidden;
    #nuc-debug-modal.nuc-debug-modal-wrapper {
      display: block;
      overflow-y: scroll;
      .debug-modal.open {
        display: block;
      }
    }
  }
}
#}
{#// SCRIPT

  (function(window, document){
    var hasOwn = Object.prototype.hasOwnProperty;

    function each(elements, callback) {
      if (Array.isArray(elements)) {
        for (var i = 0, l = elements.length; i < l; i++) {
          callback.call(elements[i], i, elements[i]);
        }
      } else {
        for (var k in elements) {
          if (hasOwn.call(elements, k)) {
            callback.call(elements[k], k, elements[k]);
          }
        }
      }
    }

    function openModal(id){
      document.body.classList.add('nuc-debug-modal-open');

      each(document.querySelectorAll('#nuc-debug-modal .debug-modal'), function () {
        this.classList.remove('open')
      });

      document.getElementById(id).classList.add('open')
    }

    function closeModal(){
      document.body.classList.remove('nuc-debug-modal-open');

      each(document.querySelectorAll('#nuc-debug-modal .debug-modal'), function () {
        this.classList.remove('open')
      });
    }

    function registerOpenModal() {
      each(document.querySelectorAll('#nuc-debug-bar a.debug-modal-trigger'), function () {
        this.addEventListener('click', function () {
          openModal(this.getAttribute('data-debug-modal-trigger'));
        })
      });
    }

    function registerCloseModal() {
      document.getElementById('nuc-debug-modal').addEventListener('click', function (ev) {
        if (ev.target === this) {
          closeModal()
        }
      })
    }

    registerOpenModal();
    registerCloseModal();

    each(document.querySelectorAll('#nuc-debug-modal ul li'), function () {
      this.addEventListener('click', function () {
        each(document.querySelectorAll('#nuc-debug-modal ul li'), function (i, el) {
          el !== this && el.classList.remove('open')
        }.bind(this));
        this.classList.toggle('open')
      })
    });
  })(window, document);
#}
