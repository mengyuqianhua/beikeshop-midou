@php
  // 统一从商品的小红书链接读取，保证种草模块与下单入口链接一致
  $xhsUrl  = product_xhs_url($product);
  $xhsCover = $product['images'][0]['preview'] ?? ($product['images'][0]['thumb'] ?? '');
@endphp

@if ($xhsUrl !== '')
  <a class="xhs-note"
     href="{{ $xhsUrl }}"
     target="_blank"
     rel="noopener noreferrer"
     aria-label="在小红书查看「{{ $product['name'] }}」的种草笔记">
    <span class="xhs-note__media">
      @if ($xhsCover)
        <img src="{{ $xhsCover }}" alt="{{ $product['name'] }}" loading="lazy" width="84" height="84">
      @endif
      <span class="xhs-note__badge">小红书</span>
      <span class="xhs-note__play"><i class="bi bi-play-fill"></i></span>
    </span>
    <span class="xhs-note__body">
      <span class="xhs-note__title-row">
        <span class="xhs-note__logo" aria-hidden="true">
          <img src="{{ asset('image/platform/xiaohongshu.svg') }}" alt="" width="12" height="12">
        </span>
        <strong class="xhs-note__title">小红书种草笔记</strong>
      </span>
      <span class="xhs-note__desc">真实使用分享 · 点击查看</span>
      <span class="xhs-note__cta">打开笔记<i class="bi bi-arrow-right"></i></span>
    </span>
  </a>
@endif
