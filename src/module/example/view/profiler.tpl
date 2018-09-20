<html>

<head>
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/2.10.0/github-markdown.css">
  </script>
  <style>
    .markdown-body {
      box-sizing: border-box;
      min-width: 200px;
      max-width: 980px;
      margin: 0 auto;
      padding: 45px;
    }

    @media (max-width: 767px) {
      .markdown-body {
        padding: 15px;
      }
    }
  </style>
</head>

<body>
  <article class="markdown-body">
    <h1>Profiler</h1>
    <a href="{$url_base}">Back to Index</a>
    <h2>Multiple Sample</h2>
    <!-- START BLOCK: sample -->
    <h3>{$label}</h3>
    <pre><code>{$example}</code></pre>
    <table>
      <thead>
        <tr>
          <th>Parameter</th>
          <th>Value</th>
        </tr>
      </thead>
      <tbody>
        <!-- START BLOCK: statistic -->
        <tr>
          <td>{$parameter}</td>
          <td>{$value}</td>
        </tr>
        <!-- END BLOCK: statistic -->
      </tbody>
    </table>
    <!-- END BLOCK: sample -->
    <h2>Sample A to Sample C</h2>
    <table>
      <thead>
        <tr>
          <th>Parameter</th>
          <th>Value</th>
        </tr>
      </thead>
      <tbody>
        <!-- START BLOCK: statistic-a-to-c -->
        <tr>
          <td>{$parameter}</td>
          <td>{$value}</td>
        </tr>
        <!-- END BLOCK: statistic-a-to-c -->
      </tbody>
    </table>
    <h2>Profiler initialize to Sample C</h2>
    <table>
      <thead>
        <tr>
          <th>Parameter</th>
          <th>Value</th>
        </tr>
      </thead>
      <tbody>
        <!-- START BLOCK: statistic-init-to-c -->
        <tr>
          <td>{$parameter}</td>
          <td>{$value}</td>
        </tr>
        <!-- END BLOCK: statistic-init-to-c -->
      </tbody>
    </table>
    <h2>Profiler initialize to Sample B</h2>
    <table>
      <thead>
        <tr>
          <th>Parameter</th>
          <th>Value</th>
        </tr>
      </thead>
      <tbody>
        <!-- START BLOCK: statistic-init-to-b -->
        <tr>
          <td>{$parameter}</td>
          <td>{$value}</td>
        </tr>
        <!-- END BLOCK: statistic-init-to-b -->
      </tbody>
    </table>
  </article>
</body>

</html>
