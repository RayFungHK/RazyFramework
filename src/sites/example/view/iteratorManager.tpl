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
    <h1>IteratorManager and Invoker Test</h1>
    <a href="{$site_root}">Back to Index</a>
    <h2>Example Data</h2>
    <pre>$data = [
  'Country' => [
    'HK' => 'Hong Kong',
    'TW' => 'Taiwan',
    'CN' => 'China'
  ],
  'Description' => '  This is a Testing Variable.   ',
  'Modified' => false
];</pre>
    <h2>Load all data</h2>
    <!-- START BLOCK: data -->
    <h3>{$index}</h3>
    <pre>{$value}</pre>
    <!-- END BLOCK: data -->
    <h2>Convertor `JSON encode` Counrtry (Chainable)</h2>
    <pre>{$json}</pre>
    <h2>Convertor `trim` Description (Chainable)</h2>
    <pre>{$trim}</pre>
    <h2>Convertor `is_email` Email determine (Non-Chainable)</h2>
    <pre>{$is_email}</pre>
  </article>
</body>

</html>
