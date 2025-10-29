<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        {? title}
            {title}
        {:}
            CI-Starter
        {/}
    </title>

    {BASE_CSS}
    {CSS}
</head>
<body>

    {? this->viewDefined('content')}
        {# content}
    {/}

    {JS}
</body>
</html>
