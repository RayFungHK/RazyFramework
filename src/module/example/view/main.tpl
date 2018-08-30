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
    {repeat count="2"}
    <p>
      Repeat: {repeat count="10"} Hello! {/repeat}
    </p>
    {/repeat}
    <p>
      This Razy Framework is developed by {$showname}{$authur}{/$showname}
    </p>

    <h1>DOMParser Selector</h1>
    <!-- START BLOCK: selector -->
    <table>
      <thead>
        <tr>
          <th colspan="2">{$selector}</th>
        </tr>
        <tr>
          <th>Tag Name</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        <!-- START BLOCK: element -->
        <tr>
          <td>{$name}</td>
          <td>{$count}</td>
        </tr>
        <!-- END BLOCK: element -->
      </tbody>
    </table>
    <!-- END BLOCK: selector -->
    <h1>Template Block Operation & Selector</h1>
    <!-- START BLOCK: levelA -->
    <p>
      index: {$index}, name: {$name}
    </p>
    <!-- END BLOCK: levelA -->

  </article>
  <article id="markdown" class="markdown-body">
    {$markdown}
  </article>
</body>

</html>
