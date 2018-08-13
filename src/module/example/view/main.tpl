<html>
<head>
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/2.10.0/github-markdown.css"></script>
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
      Repeat:
      {repeat count="10"}
      Hello!
      {/repeat}
    </p>
    {/repeat}
    <p>
      This Razy Framework is developed by {$showname}Ray Fung{/$showname}
    </p>

    <h1>Template Block Operation & Selector</h1>
    <!-- START BLOCK: levelA -->
    <p>
      index: {$index}, name: {$name}
    </p>
    <!-- END BLOCK: levelA -->

    {$markdown}
  </article>
</body>
</html>
