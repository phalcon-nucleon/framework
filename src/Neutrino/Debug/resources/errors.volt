<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8"/>
  <title>Error</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway:100,300,400"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css"/>

  {# Let browser know website is optimized for mobile #}
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <style rel="stylesheet">
pre.sql{white-space: pre-line; word-break: break-all; font-size: 13px !important;margin:0}pre.sql .string{color:#a5d6a7 !important}pre.sql .table{color:#90caf9 !important}pre.sql .column{color:#ce93d8 !important}pre.sql .func{color:#fdd835 !important}pre.sql .keyw{color:#fb8c00 !important}
  </style>
</head>
<body class="grey darken-3 grey-text text-lighten-3">

<div class="row">
  <div class="col s12">
    <ul class="tabs grey darken-4">
      <li class="tab col s3">
        <a class="active" href="#error">
          {% if isException %}
            Exception{{ exceptions | length > 1 ? 's' : '' }}
            <span class="chip">{{ exceptions | length }}</span>
          {% else %}
            Fatal error
          {% endif %}
        </a>
      </li>
      {% if profilers is not empty %}
        <li class="tab col s3 {{ profilers | length is empty ? 'disabled' : '' }}">
          <a href="#profilers">Profilers <span class="chip">{{ profilers | length }}</span></a>
        </li>
      {% endif %}
      {% if php_errors is defined %}
        <li class="tab col s3 {{ php_errors | length is empty ? 'disabled' : '' }}">
          <a href="#php-errors">Errors <span class="chip">{{ php_errors | length }}</span></a>
        </li>
      {% endif %}
      {% if events is defined %}
        <li class="tab col s3 {{ events | length is empty ? 'disabled' : '' }}">
          <a href="#events">Events <span class="chip">{{ events | length }}</span></a>
        </li>
      {% endif %}
    </ul>
  </div>
  <div id="error" class="col s12">
    {% if error['isException'] %}
      {% for exception in exceptions %}
        <div class="card grey lighten-3">
          <div class="card-content">
      <span class="card-title red-text text-accent-4">
        #{{ loop.index }} <b>{{ exception['class'] }}</b>
        <br/>
      <small class="grey-text text-darken-4">
        <b>in : </b> {{ (exception['file']) | file_highlight }}
        (line: {{ exception['line'] }})
      </small>
        <pre style="
        word-break: break-all;
        max-width:  100%;
        margin: 0;
        overflow: hidden;
        white-space: pre-line;
"><small class="grey-text text-darken-3">{{ exception['message'] | default('no message') }}</small></pre>
      </span>
            <div>
              <ul class="collection">
                {% for trace in exception['traces'] %}
                  <li class="collection-item blue-grey lighten-3 white-text">
                <span class="grey-text text-darken-3">
                  {{ trace['func'] | func_highlight }}
                </span>
                    <br/>
                    <small class="grey-text text-darken-3">
                      in :
                      {% if trace['file'] is defined %}
                        {{ trace['file'] | file_highlight }}
                        {% if trace['line'] is defined %}
                          &nbsp;(line: {{ trace['line'] }})
                        {% endif %}
                      {% else %}
                        [internal function]
                      {% endif %}
                    </small>
                  </li>
                {% endfor %}
              </ul>
            </div>
          </div>
        </div>
      {% endfor %}
    {% else %}
      <div class="card grey lighten-3">
        <div class="card-content">
      <span class="card-title red-text text-accent-4">
      {{ error['typeStr'] }}
        <br/>
      <small
        class="grey-text text-darken-4"><b>in : </b> {{ (error['file']) | file_highlight }}
        (line: {{ error['line'] }})</small>
        <pre style="
        word-break: break-all;
        max-width:  100%;
        margin: 0;
        overflow: hidden;
        white-space: pre-line;
"><small class="grey-text text-darken-3">{{ error['message'] | default('no message') }}</small></pre>
      </span>
        </div>
      </div>
    {% endif %}
  </div>
  {% if profilers is not empty %}
  <div id="profiles" class="col s12">
    <div class="card grey darken-4">
      <div class="col s12">
      <ul class="tabs grey darken-4">
      {% for name in profilers | keys %}
        <li class="tab col s3">
          <a href="#profilers-{{ name }}">{{ name }}</a>
        </li>
      {% endfor %}
      </ul>
      </div>
      {% for name, elements in profilers %}
        {% set profiler = elements['profiler'] %}
        {% set profiles = profiler.getProfiles() | default([]) %}
          <div id="profilers-{{ name }}">
            <table style="margin: 0;padding: 0;" class="bordered">
              <thead>
              <tr class="grey darken-4">
                <th style="padding: 5px 10px;border-radius: 0">-</th>
                <th style="padding: 5px 10px;border-radius: 0">sql</th>
                <th style="padding: 5px 10px;border-radius: 0">vars</th>
              </tr>
              </thead>
              <tbody>
              {% for profile in profiles %}
                <tr class="grey darken-4">
                  <td style="padding: 5px 10px;border-radius: 0">
                    <small style="white-space: nowrap;">{{ profile.getTotalElapsedSeconds() | human_mtime }}</small>
                  </td>
                  <td style="padding: 5px 10px;border-radius: 0">
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
      {% endfor %}
    </div>
  </div>
  {% endif %}
  {% if php_errors is defined %}
    <div id="php-errors" class="col s12">
      <div class="card grey darken-4">
        <div class="card-content">
          <table style="margin: 0;padding: 0;">
            <thead>
            <tr class="grey darken-4">
              <th style="padding: 5px 10px;border-radius: 0">-</th>
              <th style="padding: 5px 10px;border-radius: 0">type</th>
              <th style="padding: 5px 10px;border-radius: 0">src</th>
              <th style="padding: 5px 10px;border-radius: 0">data</th>
            </tr>
            </thead>
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
      </div>
    </div>
  {% endif %}
  {% if events is defined %}
    <div id="events" class="col s12">
      <div class="card grey darken-4">
        <div class="card-content">
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
              <td style="padding: 5px 10px">
                <small>0 ns</small>
              </td>
              <td style="padding: 5px 10px">
                <small class="event">
                  REQUEST_TIME_FLOAT
                </small>
              </td>
              <td style="padding: 5px 10px">
              </td>
              <td style="padding: 5px 10px">
              </td>
            </tr>
            {% for event in events | default([]) %}
              <tr class="grey darken-4" style="padding: 5px 10px">
                <td style="padding: 5px 10px;border-radius: 0">
                  <small  style="white-space: nowrap;">{{ (event['mt'] - mt_start) | human_mtime }}</small>
                </td>
                <td style="padding: 5px 10px;border-radius: 0">
                  <small style="white-space: nowrap;">
                    <span class="blue-text text-lighten-3">{{ event['space'] }}</span>:
                    <span class="purple-text text-lighten-3">{{ event['type'] }}</span>
                  </small>
                </td>
                <td style="padding: 5px 10px;border-radius: 0">
                  <small>{{ event['src'] }}</small>
                </td>
                <td style="padding: 5px 10px;border-radius: 0">
                  <small title="{{ is_string(event['raw_data']) ? event['raw_data'] : '' }}">{{ event['data'] }}</small>
                </td>
              </tr>
            {% endfor %}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  {% endif %}
</div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>
</body>
</html>
