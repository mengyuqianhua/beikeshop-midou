@php
    /*
     | 报错页静态兜底（前台）。
     |
     | 仅当 Guard 插件不可用时才会被使用；不访问数据库、不依赖构建产物，
     | 保证任何情况下都能渲染一张干净、无内部信息的错误页。
     */
    $code  = (int) ($status ?? 500);
    $code  = in_array($code, [400, 401, 403, 404, 405, 419, 429, 500, 502, 503, 504], true) ? $code : 500;

    // 中英双语文案：[英文标题, 中文标题, 英文说明, 中文说明]
    $texts = [
        400 => ['Bad Request', '请求无法处理', 'The request could not be processed. Please check and try again.', '请求参数有误，请检查后重试。'],
        401 => ['Authentication Required', '需要登录', 'Your session has expired. Please sign in to continue.', '登录状态已失效，请重新登录后继续操作。'],
        403 => ['Access Denied', '无访问权限', 'You do not have permission to access this page.', '您没有权限访问该页面或执行该操作。'],
        404 => ['Page Not Found', '页面不存在', 'The page you requested does not exist or has been removed.', '您访问的页面不存在、已被删除，或链接地址有误。'],
        405 => ['Method Not Allowed', '请求方式不支持', 'This request method is not supported.', '当前页面不支持该请求方式。'],
        419 => ['Page Expired', '页面已过期', 'The page has expired. Please refresh and try again.', '页面停留时间过长或登录状态已失效，请刷新后重试。'],
        429 => ['Too Many Requests', '操作过于频繁', 'Too many requests. Please try again later.', '请求过于频繁，请稍后再试。'],
        500 => ['Service Unavailable', '服务暂时不可用', 'Something went wrong. Please try again later.', '服务出现异常，请稍后重试。'],
        502 => ['Service Unavailable', '服务暂时不可用', 'The upstream service is temporarily unavailable.', '上游服务暂时不可用，请稍后重试。'],
        503 => ['Under Maintenance', '系统维护中', 'The system is under maintenance. Please try again later.', '系统正在维护升级，请稍后重试。'],
        504 => ['Gateway Timeout', '响应超时', 'The service timed out.', '服务响应超时，请稍后重试。'],
    ];
    [$titleEn, $titleZh, $descEn, $descZh] = $texts[$code];

    $zh    = str_starts_with(strtolower((string) app()->getLocale()), 'zh');
    $brand = (string) config('app.name', 'BeikeShop');
    $brand = $brand !== '' && $brand !== 'Laravel' ? $brand : 'BeikeShop';
    $home  = '/';
    try {
        $home = (string) shop_route('home.index');
    } catch (\Throwable $e) {
        $home = '/';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $zh ? 'zh-CN' : 'en' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{{ $code }} - {{ $brand }}</title>
<style>
:root{
  --bg:#f6f1e6;--bg2:#ede5d3;--ink:#221b11;--muted:#7d7261;
  --accent:#a26a2c;--accent-soft:rgba(162,106,44,.10);
  --line:rgba(34,27,17,.16);--line-strong:rgba(34,27,17,.36);
  --btn-bg:#221b11;--btn-text:#f6f1e6;
  --font-display:'Bodoni MT','Didot','Didot LT STD','Hoefler Text',Garamond,'Times New Roman','Songti SC','STSong',serif;
  --font-body:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue','PingFang SC','Hiragino Sans GB','Microsoft YaHei',sans-serif;
}
@media (prefers-color-scheme: dark){
  :root{
    --bg:#161209;--bg2:#1d1710;--ink:#ece3cf;--muted:#9c9080;
    --accent:#d2a15c;--accent-soft:rgba(210,161,92,.12);
    --line:rgba(236,227,207,.16);--line-strong:rgba(236,227,207,.38);
    --btn-bg:#ece3cf;--btn-text:#1c1608;
  }
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{
  font-family:var(--font-body);color:var(--ink);min-height:100vh;
  display:flex;flex-direction:column;padding:0 clamp(20px,5vw,64px);
  background:
    radial-gradient(1000px 560px at 88% -12%,var(--accent-soft),transparent 70%),
    radial-gradient(880px 520px at -8% 108%,var(--accent-soft),transparent 70%),
    linear-gradient(172deg,var(--bg) 0%,var(--bg2) 100%);
  -webkit-font-smoothing:antialiased;
}
.topbar{display:flex;justify-content:space-between;align-items:baseline;gap:16px;padding-top:26px;padding-bottom:16px;border-bottom:1px solid var(--line)}
.wordmark{font-family:var(--font-display);font-size:14px;letter-spacing:.34em;text-transform:uppercase}
.topbar-meta{font-size:10.5px;letter-spacing:.24em;text-transform:uppercase;color:var(--muted)}
.stage{flex:1;width:100%;max-width:1160px;margin:0 auto;display:flex;align-items:center;gap:clamp(30px,5.5vw,76px);padding:clamp(36px,7vh,72px) 0}
.numeral{font-family:var(--font-display);font-weight:500;font-size:clamp(120px,21vw,236px);line-height:.84;letter-spacing:-.02em;user-select:none}
.numeral .point{color:var(--accent)}
.rule{flex:0 0 1px;align-self:stretch;background:linear-gradient(180deg,transparent,var(--line-strong) 18%,var(--line-strong) 82%,transparent)}
.content{max-width:520px;min-width:0}
h1{font-family:var(--font-display);font-weight:500;font-size:clamp(30px,4.2vw,44px);line-height:1.16;letter-spacing:.01em}
h1 .h-en{display:block}
h1 .h-zh{display:block;margin-top:8px;font-size:.56em;font-weight:400;letter-spacing:.08em;color:var(--muted)}
.desc{margin-top:15px;font-size:15.5px;line-height:1.8;color:var(--muted);max-width:46ch}
.desc .d-en{display:block}
.desc .d-zh{display:block;margin-top:6px;font-size:.92em}
.actions{margin-top:34px;display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.btn{text-decoration:none;cursor:pointer}
.btn-primary{display:inline-flex;align-items:center;justify-content:center;height:48px;padding:0 30px;background:var(--btn-bg);color:var(--btn-text);font-size:12px;font-weight:600;letter-spacing:.18em;text-transform:uppercase;border-radius:3px;transition:background .25s ease,transform .25s ease}
.btn-primary:hover{background:var(--accent);transform:translateY(-1px)}
.btn-ghost{font-size:12px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);padding-bottom:3px;border-bottom:1px solid var(--line-strong);transition:color .25s ease,border-color .25s ease}
.btn-ghost:hover{color:var(--accent);border-color:var(--accent)}
footer{display:flex;align-items:center;flex-wrap:wrap;gap:9px;padding:16px 0 24px;border-top:1px solid var(--line);font-size:12px;color:var(--muted);line-height:1.7}
footer .brand{font-family:var(--font-display);font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:var(--ink)}
footer .dot{width:3px;height:3px;border-radius:50%;background:var(--muted);opacity:.55}
footer .copy{margin-left:auto}
@media (max-width:760px){
  body{padding:0 20px}
  .stage{flex-direction:column;align-items:flex-start;gap:24px;padding:40px 0}
  .rule{width:100%;flex:0 0 1px;background:linear-gradient(90deg,var(--line-strong),transparent)}
  .numeral{font-size:clamp(92px,30vw,132px)}
  .content{max-width:100%}
  footer .copy{margin-left:0;width:100%}
}
</style>
</head>
<body>
  <header class="topbar">
    <span class="wordmark">{{ $brand }}</span>
    <span class="topbar-meta">Error {{ $code }}</span>
  </header>
  <main class="stage">
    <div class="numeral" aria-hidden="true">{{ $code }}<span class="point">.</span></div>
    <div class="rule" aria-hidden="true"></div>
    <div class="content">
      <h1><span class="h-en">{{ $titleEn }}</span><span class="h-zh">{{ $titleZh }}</span></h1>
      <p class="desc"><span class="d-en">{{ $descEn }}</span><span class="d-zh">{{ $descZh }}</span></p>
      <div class="actions">
        <a class="btn btn-primary" href="{{ $home }}">返回首页 · Back to Home</a>
        <a class="btn btn-ghost" href="javascript:history.back()" rel="nofollow">返回上一页 · Go Back</a>
      </div>
    </div>
  </main>
  <footer>
    <span class="brand">{{ $brand }}</span><span class="dot"></span><span class="copy">&copy; {{ date('Y') }}</span>
  </footer>
</body>
</html>
