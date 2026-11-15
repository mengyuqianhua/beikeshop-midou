@extends('admin::layouts.master')

@section('title', __('admin/product.products_show'))

@section('body-class', 'page-product-form')

@section('page-title-back', admin_route('products.index', http_build_query(request()->query())))

@push('header')
  <script src="{{ asset('vendor/vue/Sortable.min.js') }}"></script>
  <script src="{{ asset('vendor/vue/vuedraggable.js') }}"></script>
  <script src="{{ asset('vendor/tinymce/5.9.1/tinymce.min.js') }}"></script>
@endpush

@section('head-form-btns', true)

@section('content')
  <style>
    /* ---------- 商品发布页：参考抖音 / 小红书的填写动线 ---------- */
    .page-product-form .pf-card {
      border: 1px solid #eef0f4;
      border-radius: 14px;
      box-shadow: 0 2px 10px rgba(17, 24, 39, .04);
    }
    .page-product-form .pf-card > .card-body { padding: 22px 24px; }
    .page-product-form .pf-head {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 18px;
    }
    .page-product-form .pf-head__idx {
      flex: 0 0 26px;
      width: 26px;
      height: 26px;
      margin-top: 1px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 700;
      color: #fff;
      background: linear-gradient(135deg, #ff4d6a, #ff2e4d);
    }
    .page-product-form .pf-head__title {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: #111827;
      line-height: 26px;
    }
    .page-product-form .pf-head__sub {
      margin: 2px 0 0;
      font-size: 12px;
      color: #8a8f99;
      line-height: 1.6;
    }
    .page-product-form .pf-head__extra { margin-left: auto; }

    /* 表单字段更宽松的行距，贴近内容平台后台 */
    .page-product-form .row > [class*="col-"] > .mb-3,
    .page-product-form .form-group { margin-bottom: 20px; }
    .page-product-form .col-form-label { color: #4b5563; font-weight: 500; }

    /* 主图区域 */
    .page-product-form .product-images-box {
      border: 1.5px dashed #e3e6ec;
      border-radius: 12px;
      background: #fafbfc;
      padding: 12px;
      transition: border-color .2s, background .2s;
    }
    .page-product-form .product-images-box.drag-over { border-color: #ff2e4d; background: #fff5f6; }
    .page-product-form .product-images { gap: 10px; }
    .page-product-form .product-item { border-radius: 10px; overflow: hidden; }
    .page-product-form .set-product-img {
      border-radius: 10px;
      border: 1.5px dashed #e3e6ec;
      background: #fff;
    }
    .page-product-form .set-product-img:hover { border-color: #ff2e4d; }

    /* 价格区 */
    .page-product-form .pf-price-row .form-control {
      height: 46px;
      font-size: 18px;
      font-weight: 600;
      color: #111827;
    }
    .page-product-form .pf-price-row .form-control::placeholder { font-size: 14px; font-weight: 400; }
    .page-product-form .pf-cur-toggle {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      cursor: pointer;
      user-select: none;
      font-weight: 600;
      transition: color .2s, background .2s, border-color .2s;
    }
    .page-product-form .pf-cur-toggle:hover {
      color: #ff2e4d;
      background: #fff5f6;
      border-color: #ffd0d7;
    }
    .page-product-form .pf-cur-toggle__icon { font-size: 11px; opacity: .6; }

    /* 换算面板 */
    .page-product-form .pf-convert {
      margin-top: 4px;
      border: 1px solid #ffe0e5;
      border-radius: 12px;
      background: linear-gradient(180deg, #fff8f9, #fff);
      padding: 14px 16px;
    }
    .page-product-form .pf-convert__top {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px 14px;
      margin-bottom: 12px;
    }
    .page-product-form .pf-convert__title { font-size: 13px; font-weight: 600; color: #111827; }
    .page-product-form .pf-convert__rate {
      font-size: 12px;
      color: #b42318;
      background: #fff1f3;
      border-radius: 999px;
      padding: 2px 10px;
    }
    .page-product-form .pf-convert__meta { font-size: 12px; color: #8a8f99; }
    .page-product-form .pf-convert__refresh {
      margin-left: auto;
      border: 0;
      background: transparent;
      font-size: 12px;
      color: #ff2e4d;
      padding: 0;
      cursor: pointer;
    }
    .page-product-form .pf-convert__refresh:hover { text-decoration: underline; }
    .page-product-form .pf-convert__query {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      gap: 12px;
    }
    .page-product-form .pf-convert__field {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin: 0;
      font-weight: 400;
    }
    .page-product-form .pf-convert__field-label { font-size: 12px; color: #8a8f99; }
    .page-product-form .pf-convert__amount { width: 150px; height: 38px; }
    .page-product-form .pf-convert__currency { width: 168px; height: 38px; }
    .page-product-form .pf-convert__results {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-left: auto;
    }
    .page-product-form .pf-convert__result {
      display: inline-flex;
      align-items: baseline;
      gap: 8px;
      border: 1px solid #ffe0e5;
      border-radius: 10px;
      background: #fff;
      padding: 7px 12px;
    }
    .page-product-form .pf-convert__result em {
      font-style: normal;
      font-size: 12px;
      color: #8a8f99;
    }
    .page-product-form .pf-convert__result b {
      font-size: 15px;
      color: #111827;
      font-variant-numeric: tabular-nums;
    }
    .page-product-form .pf-convert__result.is-empty b { font-weight: 400; color: #b3b8c2; }
    .page-product-form .pf-convert__tip { margin: 10px 0 0; font-size: 12px; color: #9aa0aa; }

    /* 图文信息区：图片容器固定正方尺寸，说明文字独立分区留白 */
    .page-product-form .product-images-box {
      padding: 16px;
      border-radius: 14px;
      background: #fafbfc;
    }
    .page-product-form .product-images {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
      gap: 14px;
    }
    .page-product-form .product-images .product-item {
      width: 100%;
      height: auto;
      aspect-ratio: 1 / 1;
      border-radius: 10px;
      background: #fff;
    }
    .page-product-form .product-images .product-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .page-product-form .product-images .product-item:first-of-type {
      grid-column: span 2;
      grid-row: span 2;
      aspect-ratio: auto;
      height: 100%;
    }
    .page-product-form .product-images .set-product-img {
      width: 100%;
      height: auto;
      aspect-ratio: 1 / 1;
      min-height: 0;
    }
    .page-product-form .product-images-box .help-text,
    .page-product-form .help-text {
      margin-top: 14px !important;
      line-height: 1.65;
      color: #9aa0a6;
    }
    .page-product-form .pf-card .card-body { padding: 24px; }
    .page-product-form .row > [class*="col-"] > .mb-3,
    .page-product-form .form-group { margin-bottom: 22px; }

    /* 商品详情图独立模块 */
    .pdim__head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
    }
    .pdim__title { margin: 0 0 2px; font-size: 15px; font-weight: 600; color: #1a1a1a; }
    .pdim__desc { margin: 0; font-size: 12px; color: #9aa0a6; }
    .pdim__upload {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      width: 100%;
      padding: 28px 16px;
      border: 1.5px dashed #d9dce1;
      border-radius: 14px;
      background: #fafbfc;
      color: #666;
      cursor: pointer;
      transition: all .2s ease;
      text-align: center;
    }
    .pdim__upload:hover,
    .pdim__upload.is-dragover {
      border-color: #ff2e4d;
      background: #fff6f7;
      color: #ff2e4d;
    }
    .pdim__upload i { font-size: 26px; }
    .pdim__upload-title { font-size: 14px; font-weight: 600; }
    .pdim__upload-tip { font-size: 12px; color: #9aa0a6; }
    .pdim__status {
      margin-top: 12px;
      padding: 10px 12px;
      border-radius: 10px;
      font-size: 13px;
      line-height: 1.6;
    }
    .pdim__status--info { background: #eef5ff; color: #1a6bd8; }
    .pdim__status--success { background: #eefaf1; color: #1a9c53; }
    .pdim__status--error { background: #fff1f2; color: #d92d3f; }
    .pdim__list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 14px;
      margin-top: 16px;
    }
    .pdim__empty { margin-top: 12px; color: #9aa0a6; font-size: 13px; text-align: center; }
    .pdim-item {
      position: relative;
      background: #fff;
      border: 1px solid #eceef1;
      border-radius: 12px;
      padding: 8px;
    }
    .pdim-item.is-dragging { opacity: .45; }
    .pdim-item__thumb {
      position: relative;
      width: 100%;
      aspect-ratio: 1 / 1;
      border-radius: 8px;
      overflow: hidden;
      background: #f2f3f5;
    }
    .pdim-item__thumb img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .pdim-item__index {
      position: absolute;
      top: 14px;
      left: 14px;
      min-width: 22px;
      height: 22px;
      padding: 0 6px;
      border-radius: 11px;
      background: rgba(0, 0, 0, .55);
      color: #fff;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .pdim-item__badge {
      position: absolute;
      top: 14px;
      right: 14px;
      font-size: 11px;
      color: #fff;
      background: rgba(255, 46, 77, .85);
      border-radius: 6px;
      padding: 1px 6px;
    }
    .pdim-item__actions { display: flex; gap: 4px; margin-top: 8px; }
    .pdim-item__actions button {
      flex: 1;
      border: 1px solid #e6e8eb;
      background: #fff;
      border-radius: 8px;
      font-size: 12px;
      padding: 5px 0;
      color: #555;
    }
    .pdim-item__actions button:hover { border-color: #ff2e4d; color: #ff2e4d; }
    .pdim-item__actions .is-danger:hover { border-color: #d92d3f; color: #d92d3f; }
    .pdim-modal { position: fixed; inset: 0; z-index: 20000; display: flex; align-items: center; justify-content: center; }
    .pdim-modal[hidden] { display: none; }
    .pdim-modal__mask { position: absolute; inset: 0; background: rgba(0, 0, 0, .55); }
    .pdim-modal__panel {
      position: relative;
      width: min(720px, 92vw);
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 24px 60px rgba(0, 0, 0, .25);
    }
    .pdim-modal__head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid #f0f1f3;
      font-weight: 600;
    }
    .pdim-modal__close { border: 0; background: transparent; font-size: 22px; line-height: 1; color: #999; }
    .pdim-modal__body {
      height: min(56vh, 460px);
      background: #0f1115;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .pdim-modal__body img { max-width: 100%; max-height: 100%; display: block; }
    .pdim-modal__foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; }
    .pdim-modal__hint { font-size: 12px; color: #9aa0a6; }

    @media (max-width: 767.98px) {
      .pdim__list { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; }
      .pdim-modal__foot { flex-direction: column; align-items: stretch; }
    }

    /* 右侧栏吸顶 */
    .page-product-form .pf-sidebar { position: sticky; top: 12px; }

    @media (max-width: 991px) {
      .page-product-form .pf-sidebar { position: static; }
      .page-product-form .pf-convert__results { margin-left: 0; }
    }

    @media (max-width: 767.98px) {
      .page-product-form .pf-card .card-body { padding: 16px; }
      .page-product-form .product-images-box { padding: 12px; }
      .page-product-form .product-images {
        grid-template-columns: repeat(auto-fill, minmax(84px, 1fr));
        gap: 10px;
      }
    }
  </style>
  @if (session()->has('success'))
    <x-admin-alert type="success" msg="{{ session('success') }}" class="mt-4"/>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif
  <form novalidate class="needs-validation"
        action="{{ $product->id ? admin_route('products.update', $product) : admin_route('products.store') }}"
        method="POST" id="app" v-cloak>
    @csrf
    @method($product->id ? 'PUT' : 'POST')
    <input type="hidden" name="_redirect" value="{{ $_redirect }}"/>

    @hook('admin.product.form.content.before')
    <div class="row g-3">
      <div class="col-lg-9">
        @hook('admin.product.form.main.before')

        <div class="card mb-3 pf-card">
          <div class="card-body">
            <div class="pf-head">
              <span class="pf-head__idx">1</span>
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_basic') }}</h5>
                <p class="pf-head__sub">{{ __('admin/product.section_basic_desc') }}</p>
              </div>
            </div>

            @hook('admin.product.form.main.card.data.before')

            @hook('admin.product.name.before')
            <x-admin-form-input-locale name="descriptions.*.name" title="{{ __('common.name') }}" :value="$descriptions" :required="true"/>
            @hook('admin.product.name.after')
            <x-admin::form.row title="">
              <div class="col-form-label d-flex align-items-center">
                <div>{{ __('admin/product.images_video') }}</div>
                <div class="btn btn-default btn-sm ms-3" @click="addProductImageUrl">{{ __('admin/product.add_url') }}</div>
              </div>
              <div
                :class="{
                  'product-images-box': true,
                  'drag-over': imagesHandle.isDragOver,
                  'no-images': !form.images.length,
                }"
                @dragover.prevent="handleDragOver"
                @dragleave="handleDragLeave"
                @drop.prevent="onDrop"
                >
                <span class="drag-drop-upload-text"><i class="bi bi-layers-half"></i> {{ __('admin/product.drag_drop_upload') }}</span>
                <div class="product-images-bans" v-if="!form.images.length" @click="productsUploadNew">
                  <label class="btn btn-light btn-sm upload-new" @click.stop>
                    <i class="bi bi-cloud-upload"></i> {{ __('admin/product.products_upload_new') }}
                    <input type="file" class="d-none" @change="newFileChange" multiple id="new-file-input" name="" accept="image/*">
                  </label>
                  <a class="btn btn-light btn-sm ms-2 upload-file-manager" href="javascript:void(0);" @click.stop="addProductImages('all')"><i class="bi bi-images"></i> {{ __('admin/product.file_manager_select') }}</a>
                </div>
                <draggable
                  element="div"
                  ghost-class="dragabble-ghost"
                  class="product-images"
                  :list="form.images"
                  :move="draggableCheckMove"
                  :animation="200"
                  :handle="'.product-item'"
                  :filter="'.no-drag'"
                >
                  <div
                    v-for="image, index in form.images"
                    :key="index"
                    :class="{
                      'product-item': true,
                      'product-item-video': isVideo(image),
                      'position-relative': true,
                      'border': true,
                      'd-flex': true,
                      'justify-content-center': true,
                      'align-items-center': true,
                      'max-h-100': true,
                      'overflow-hidden': true,
                      'd-none': !imagesHandle.showAll && index > 7,
                      'no-drag': !imagesHandle.showAll && index == 7
                    }"
                    @click="isVideo(image) ? showVideo(image) : ''"
                  >
                    <div class="position-absolute top-0 end-0 z-3" v-if="imagesHandle.showAll || index != 7">
                      <a v-if="isVideo(image)" class="btn btn-info btn-sm wh-20 p-0 text-white" :href="form.video.path" target="_blank" type="button"><i class="bi bi-play-fill"></i></a>
                      <button class="btn btn-danger btn-sm wh-20 p-0" @click.stop="removeImages(index)" type="button"><i class="bi bi-trash"></i></button>
                    </div>
                    <div v-if="!imagesHandle.showAll && index == 7" class="product-item-omit" @click="imagesHandle.showAll = true">
                      +@{{ form.images.length - (productImages.length - 1) }}
                    </div>
                    <div v-if="isVideo(image)">
                      <video :src="image" v-if="!isYouTubeVideoUrl(image)" class="img-fluid" preload="metadata"></video>
                      <img v-else :src="'https://i.ytimg.com/vi/' + getYouTubeVideoId(image) + '/maxresdefault.jpg'" class="img-fluid">
                      <input type="hidden" name="video" :value="form.video.path">
                    </div>
                    <div v-else>
                      <img :src="thumbnail(image) + (thumbnail(image).indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now()" class="img-fluid">
                    </div>
                    <input type="hidden" name="images[]" :value="image">
                  </div>
                  <div class="set-product-img" v-if="form.images.length" @click="addProductImages('all')"><i
                      class="bi bi-plus fs-1 text-muted"></i></div>
                </draggable>
              </div>
              <div class="help-text mb-1 mt-1 w-max-600">{{ __('admin/product.image_help') }}</div>

              <div class="mt-3">
                <label class="form-label fw-bold" for="xhs-note-url">小红书种草笔记链接（可选）</label>
                <input id="xhs-note-url" type="text" class="form-control" v-model.trim="form.video.path"
                       placeholder="https://www.xiaohongshu.com/explore/... 或 https://xhslink.com/...">
                <div class="help-text mb-1 mt-1 w-max-600">
                  填写小红书笔记分享链接后，前台商品详情页会展示「小红书种草笔记」跳转卡片；留空则不展示。
                  该链接与商品视频共用同一字段，二者只能选其一（填写链接会覆盖已上传的视频）。
                </div>
                <div class="mt-1" v-if="form.video.path && isXhsNoteUrl(form.video.path)">
                  <a :href="form.video.path" target="_blank" rel="noopener noreferrer">打开该小红书笔记 ↗</a>
                </div>
                <input type="hidden" name="video" :value="form.video.path">
              </div>

              <div class="mt-3">
                <label class="form-label fw-bold" for="xhs-url">小红书链接（可选）</label>
                <input id="xhs-url" name="xhs_url" type="text" class="form-control" v-model.trim="form.xhs_url"
                       placeholder="https://www.xiaohongshu.com/explore/...">
                <div class="help-text mb-1 mt-1 w-max-600">
                  小红书种草笔记与未登录下单入口共用此链接；留空时自动兼容「视频链接」中填写的小红书链接。
                </div>
              </div>

              <div class="mt-3">
                <label class="form-label fw-bold" for="taobao-url">淘宝商品链接（可选）</label>
                <input id="taobao-url" name="taobao_url" type="text" class="form-control" v-model.trim="form.taobao_url"
                       placeholder="https://item.taobao.com/item.htm?id=...">
                <div class="help-text mb-1 mt-1 w-max-600">
                  填写淘宝商品详情页链接后，未登录用户点击「立即购买」时会展示淘宝下单入口及二维码；留空则使用按商品名称搜索的淘宝链接。
                </div>
              </div>

              <div class="mt-3">
                <label class="form-label fw-bold" for="douyin-url">抖音商品链接（可选）</label>
                <input id="douyin-url" name="douyin_url" type="text" class="form-control" v-model.trim="form.douyin_url"
                       placeholder="https://haohuo.jinritemai.com/views/product/item2?id=...">
                <div class="help-text mb-1 mt-1 w-max-600">
                  填写抖音商品详情页链接后，未登录用户点击「立即购买」时会展示抖音下单入口及二维码；留空则使用按商品名称搜索的抖音链接。
                </div>
              </div>
            </x-admin::form.row>
          </div>
        </div>

        <div class="card mb-3 pf-card">
          <div class="card-body">
            <div class="pf-head">
              <span class="pf-head__idx">2</span>
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_price') }}</h5>
                <p class="pf-head__sub">{{ __('admin/product.section_price_desc') }}</p>
              </div>
            </div>

            <div>
              @hookwrapper('admin.product.edit.variable')
              <div v-if="!editing.isVariable">
                <input type="hidden" value="{{ old('skus.0.image', $product->skus[0]->image ?? '') }}"
                        name="skus[0][image]">
                <div class="row pf-price-row">
                  <div class="col-12 col-xl-4">
                    <x-admin-form-input name="skus[0][price]" type="number" :title="__('admin/product.price')"
                    :group-right="$system_currency" :value="old('skus.0.price', $product->skus[0]->price ?? '')" step="any" required/>
                  </div>
                  <div class="col-12 col-xl-4">
                    <x-admin-form-input name="skus[0][origin_price]" type="number" :title="__('admin/product.origin_price')"
                    :group-right="$system_currency" :value="old('skus.0.origin_price', $product->skus[0]->origin_price ?? '')" step="any"/>
                  </div>
                  <div class="col-12 col-xl-4">
                    <x-admin-form-input name="skus[0][cost_price]" type="number" :title="__('admin/product.cost_price')"
                    :group-right="$system_currency" :value="old('skus.0.cost_price', $product->skus[0]->cost_price ?? '')" step="any"/>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 col-xl-4">
                    @php
                      $defaul_sku = 'bksku-' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 4). '-' . substr(str_shuffle('0123456789'), 0, 3);
                    @endphp
                    <x-admin-form-input name="skus[0][sku]" title="sku" :value="old('skus.0.sku', $product->skus[0]->sku ?? $defaul_sku)" required/>
                  </div>
                  <div class="col-12 col-xl-4">
                    <x-admin-form-input name="skus[0][quantity]" type="number" :title="__('admin/product.quantity')" :value="old('skus.0.quantity', $product->skus[0]->quantity ?? '')"/>
                  </div>
                  <div class="col-12 col-xl-4">
                    <x-admin-form-input name="skus[0][model]" :title="__('admin/product.model')" :value="old('skus.0.model', $product->skus[0]->model ?? '')"/>
                  </div>
                </div>

                <input type="hidden" name="skus[0][variants]" placeholder="variants" value="">
                <input type="hidden" name="skus[0][position]" placeholder="position" value="0">
                <input type="hidden" name="skus[0][is_default]" placeholder="is_default" value="1">
                @hook('admin.product.edit.sku.price.single.tiered')
              </div>
              @endhookwrapper
            </div>

            {{-- 人民币 / 美元实时换算 --}}
            <div class="pf-convert" id="pf-convert" data-endpoint="{{ admin_route('currencies.rates') }}">
              <div class="pf-convert__top">
                <span class="pf-convert__title">{{ __('admin/product.price_convert') }}</span>
                <span class="pf-convert__rate" data-role="rate">{{ __('admin/product.price_convert_loading') }}</span>
                <span class="pf-convert__meta" data-role="meta"></span>
                <button type="button" class="pf-convert__refresh" data-role="refresh">{{ __('admin/product.price_convert_refresh') }}</button>
              </div>
              <div class="pf-convert__query">
                <label class="pf-convert__field">
                  <span class="pf-convert__field-label">{{ __('admin/product.price_convert_amount') }}</span>
                  <input type="number" min="0" step="0.01" inputmode="decimal"
                         class="form-control pf-convert__amount" data-role="amount"
                         placeholder="{{ __('admin/product.price_convert_amount_placeholder') }}">
                </label>
                <label class="pf-convert__field">
                  <span class="pf-convert__field-label">{{ __('admin/product.price_convert_from') }}</span>
                  <select class="form-select pf-convert__currency" data-role="from">
                    <option value="{{ $system_currency }}">{{ __('admin/product.price_system') }} ({{ $system_currency }})</option>
                    @if ($system_currency !== 'CNY')
                      <option value="CNY">人民币 CNY</option>
                    @endif
                    @if ($system_currency !== 'USD')
                      <option value="USD">美元 USD</option>
                    @endif
                  </select>
                </label>
                <div class="pf-convert__results" data-role="results"></div>
              </div>
              <p class="pf-convert__tip">{{ __('admin/product.price_convert_query_tip') }}</p>
            </div>

            @hook('admin.product.form.main.card.data.after')
          </div>
        </div>

        <div class="card mb-3 description-wrap pf-card">
          <div class="card-body">
            <div class="pf-head">
              <span class="pf-head__idx">3</span>
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_detail') }}</h5>
                <p class="pf-head__sub">{{ __('admin/product.section_detail_desc') }}</p>
              </div>
            </div>

            @hook('admin.product.form.main.card.description.before')

            <ul class="nav nav-tabs mb-3" role="tablist">
              @foreach ($languages as $language)
                <li class="nav-item" role="presentation">
                  <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                          data-bs-target="#tab-descriptions-{{ $language->code }}"
                          type="button">{{ $language->name }}</button>
                </li>
              @endforeach
              @hook('admin.product.content.li.after')
            </ul>

            <div class="tab-content">
              @foreach ($languages as $language)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                      id="tab-descriptions-{{ $language->code }}">
                  <textarea name="descriptions[{{ $language->code }}][content]" class="form-control tinymce">
                    {{ old('content', $product->descriptions->keyBy('locale')[$language->code]->content ?? '') }}
                  </textarea>
                </div>
              @endforeach
              @hook('admin.product.content.after')
            </div>

            <div class="pdim mt-4" id="pdim"
                 data-upload-url="{{ admin_route('file_manager.upload') }}"
                 data-items="{{ json_encode($detail_images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
              <input type="hidden" name="detail_images" id="pdim-input" value="[]">

              <div class="pdim__head">
                <div>
                  <h6 class="pdim__title">详情图</h6>
                  <p class="pdim__desc">展示在详情文字下方；上传后统一宽度，高度按原比例自适应</p>
                </div>
                <button type="button" class="btn btn-light btn-sm" id="pdim-library">
                  <i class="bi bi-images me-1"></i>从图库选择
                </button>
              </div>

              <button type="button" class="pdim__upload" id="pdim-upload">
                <i class="bi bi-cloud-arrow-up"></i>
                <span class="pdim__upload-title">点击或拖拽上传详情图</span>
                <span class="pdim__upload-tip">支持 JPG / PNG / WEBP，可批量选择；大图会先优化体积再上传，保持清晰度</span>
              </button>
              <input type="file" id="pdim-file" accept="image/jpeg,image/png,image/webp" multiple hidden>

              <div class="pdim__status" id="pdim-status" hidden></div>
              <div class="pdim__list" id="pdim-list"></div>
              <div class="pdim__empty" id="pdim-empty">还没有详情图，上传后将展示在详情文字下方</div>
            </div>

            <div class="pdim-modal" id="pdim-modal" hidden>
              <div class="pdim-modal__mask" data-pdim-close></div>
              <div class="pdim-modal__panel" role="dialog" aria-modal="true">
                <div class="pdim-modal__head">
                  <span>裁切详情图</span>
                  <button type="button" class="pdim-modal__close" data-pdim-close>&times;</button>
                </div>
                <div class="pdim-modal__body">
                  <img id="pdim-crop-image" alt="">
                </div>
                <div class="pdim-modal__foot">
                  <span class="pdim-modal__hint">拖动图片调整裁切区域，滚轮或双指缩放；裁切比例由你自由决定</span>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light btn-sm" id="pdim-reset">重置</button>
                    <button type="button" class="btn btn-primary btn-sm" id="pdim-apply">应用裁切</button>
                  </div>
                </div>
              </div>
            </div>

            @hook('admin.product.form.main.card.description.after')
          </div>
        </div>

        <div :class="'card mb-3 pf-card' + (isVariableFullEdit ? ' variable-card-full-edit' : '')">
          <div class="card-body pb-0">
            <div class="pf-head">
              <span class="pf-head__idx">4</span>
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_spec') }}</h5>
                <p class="pf-head__sub">{{ __('admin/product.section_spec_desc') }}</p>
              </div>
              <div class="pf-head__extra">
                <button class="btn btn-default btn-sm" type="button" @click="isVariableFullEdit = !isVariableFullEdit" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('admin/product.full_edit') }}">
                  <i :class="isVariableFullEdit ? 'bi bi-arrows-angle-contract' : 'bi bi-arrows-angle-expand'"></i>
                </button>
              </div>
            </div>

            @hook('admin.product.form.main.card.variable.before')

            <div>
              @hookwrapper('admin.product.edit.switch')
              <x-admin::form.row title="">
                <el-switch v-model="editing.isVariable" @change="isVariableChange" class="mt-2"></el-switch>
              </x-admin::form.row>
              @endhookwrapper

              <input type="hidden" name="variables" :value="JSON.stringify(form.variables)">

              <div class="mb-3" v-if="editing.isVariable">
                <div class="">
                  <div class="selectable-variants">
                    <div>
                      <draggable :list="source.variables" :animation="100">
                        <div v-for="(variant, variantIndex) in source.variables"
                              :id="'selectable-variant-' + variantIndex">
                          <div class="title">
                            <div>
                              <b>@{{ variant.name[current_language_code] }}</b>
                              <el-link type="primary"
                                        @click="modalVariantOpenButtonClicked(variantIndex, null)">{{ __('common.edit') }}</el-link>
                              <el-link type="danger" class="ms-2"
                                        @click="removeSourceVariant(variantIndex)">{{ __('common.delete') }}</el-link>
                              @hook('admin.product.edit.source.variables.actions')
                            </div>
                            <div>
                              <el-checkbox v-model="variant.isImage" @change="(e) => {variantIsImage(e, variantIndex)}"
                                            border size="mini"
                                            class="me-2 bg-white">{{ __('admin/product.add_variable_image') }}</el-checkbox>
                              <el-button type="primary" plain size="mini"
                                          @click="modalVariantOpenButtonClicked(variantIndex, -1)">{{ __('admin/product.add_variable_value') }}</el-button>
                            </div>
                          </div>
                          <template v-if="variant.values.length">
                            <draggable
                              element="div"
                              @start="isMove = true"
                              class="variants-wrap"
                              @update="(e) => {swapSourceVariantValue(e, variantIndex)}"
                              @end="isMove = false"
                              ghost-class="dragabble-ghost"
                              :list="variant.values"
                              :animation="100"
                            >
                              <div v-for="(value, value_index) in variant.values" :key="value_index"
                                    class="variants-item"
                                    @dblclick="modalVariantOpenButtonClicked(variantIndex, value_index)">
                                <div class="open-file-manager variant-value-img" v-if="variant.isImage">
                                  <div>
                                    <img :src="thumbnail(value.image)" class="img-fluid">
                                  </div>
                                </div>
                                <input type="hidden" v-model="value.image">
                                <div class="btn-remove" @click="removeSourceVariantValue(variantIndex, value_index)"><i
                                    class="el-icon-error"></i></div>
                                <div class="name">
                                  @{{ value.name[current_language_code] }}
                                  @hook('admin.product.edit.source.variables.name.after')
                                </div>
                              </div>
                            </draggable>
                            <div class="ps-2 mt-2 mb-3 opacity-50"><i
                                class="bi bi-exclamation-circle"></i> {{ __('admin/product.modify_order') }}</div>
                          </template>
                          <div v-else class="d-flex justify-content-center align-items-center">
                            <div class="p-4 fs-5 btn" @click="modalVariantOpenButtonClicked(variantIndex, -1)"><i
                                class="bi bi-plus-square-dotted"></i> {{ __('admin/product.add_variable_value') }}</div>
                          </div>

                        </div>
                      </draggable>

                      <el-button type="primary" plain size="small" @click="modalVariantOpenButtonClicked(-1, null)"
                                  class="btn btn-xs mr-1 mb-1">{{ __('admin/product.add_variable') }}</el-button>
                    </div>
                    @hookwrapper('admin.product.edit.variables')
                    <div v-if="form.skus.length && form.variables.length" class="mt-3 table-push table-responsive">
                      <div class="batch-setting d-flex align-items-center mb-3 p-2 bg-body">
                        <div v-for="(variant, index) in form.variables" :key="index" class="me-2">
                          <select class="form-select me-2" aria-label="Default select example"
                                  v-model="variablesBatch.variables[index]">
                            <option selected value="">@{{ variant.name[current_language_code] || 'No name' }}</option>
                            <option :value="value_index" v-for="value, value_index in variant.values"
                                    :key="index+'-'+value_index">
                              @{{ value.name[current_language_code]}}
                            </option>
                          </select>
                        </div>
                        <div role="button"
                              class="border d-flex justify-content-center align-items-center border-dashed bg-light wh-40 me-2 bg-white"
                              @click="batchSettingVariantImage">
                          <img :src="thumbnail(variablesBatch.image)" class="img-fluid" v-if="variablesBatch.image"
                                style="max-height: 40px;">
                          <i class="bi bi-plus fs-3 text-muted" v-else></i>
                        </div>
                        <input type="text" class="form-control me-2" v-model="variablesBatch.model"
                                placeholder="{{ __('admin/product.model') }}">
                        <input type="text" class="form-control me-2" v-model="variablesBatch.sku"
                                placeholder="sku">
                        <input type="number" class="form-control me-2" v-model="variablesBatch.price"
                                placeholder="{{ __('admin/product.price') }}">
                        <input type="number" class="form-control me-2" v-model="variablesBatch.origin_price"
                                placeholder="{{ __('admin/product.origin_price') }}">
                        <input type="number" class="form-control me-2" v-model="variablesBatch.cost_price"
                                placeholder="{{ __('admin/product.cost_price') }}">
                        <input type="number" class="form-control me-2" v-model="variablesBatch.quantity"
                              placeholder="{{ __('admin/product.quantity') }}">
                        <input type="number" class="form-control me-2" v-model="variablesBatch.weight"
                                placeholder="{{ __('admin/product.weight_text') }}">
                        <select class="form-select me-2" v-model="variablesBatch.active">
                          <option value="">{{ __('admin/builder.text_no') }}</option>
                          <option value="1">{{ __('common.enable') }}</option>
                          <option value="0">{{ __('common.disable') }}</option>
                        </select>
                        @hook('admin.product.edit.variables.batch.input.after')
                        <button type="button" class="btn btn-primary text-nowrap"
                                @click="batchSettingVariant">{{ __('common.batch_setting') }}</button>
                        @hook('admin.product.edit.variables.batch.after')
                      </div>

                      <table class="table table-bordered table-hover table-skus table-no-mb">
                        <thead>
                        <th v-for="(variant, index) in form.variables" :key="'pv-header-' + index">
                          @{{ variant.name[current_language_code] || 'No name' }}
                        </th>
                        <th>{{ __('common.image') }}</th>
                        <th class="w-min-100">{{ __('admin/product.model') }}</th>
                        <th class="w-min-100">sku</th>
                        <th class="w-min-70">{{ __('admin/product.price') }}<span class="font-size-12 fw-normal text-secondary">({{$system_currency}})</span></th>
                        <th class="w-min-70">{{ __('admin/product.origin_price') }}<span class="font-size-12 fw-normal text-secondary">({{$system_currency}})</span></th>
                        <th class="w-min-70">{{ __('admin/product.cost_price') }}<span class="font-size-12 fw-normal text-secondary">({{$system_currency}})</span></th>
                        <th style="width: 70px">{{ __('admin/product.quantity') }}</th>
                        <th style="width: 70px">{{ __('admin/product.weight_text') }}<span class="font-size-12 fw-normal text-secondary variant-weight"></span></th>
                        <th class="w-min-100">{{ __('common.status') }}</th>
                        @hook('admin.product.edit.sku.variants.title.after')
                        </thead>
                        <tbody>
                        <tr v-for="(sku, skuIndex) in form.skus" :key="skuIndex">
                          <template v-for="(variantValueIndex, j) in sku.variants">
                            <td v-if="skuIndex % variantValueRepetitions[j] == 0" :key="'pvv' + skuIndex + '-' + j"
                                :rowspan="variantValueRepetitions[j]">
                              <span>@{{ form.variables[j].values[variantValueIndex].name[current_language_code] || 'No name' }}</span>
                            </td>
                          </template>
                          <td>
                            <div class="product-images d-flex flex-wrap" style="margin-right: -8px">
                              <div v-for="image, index in sku.images" :key="index"
                                    class="product-item wh-40 border d-flex justify-content-center align-items-center me-2 mb-2 position-relative">
                                <div class="position-absolute top-0 end-0">
                                  <button class="btn btn-danger btn-sm wh-20 p-0"
                                          @click="removeSkuImages(skuIndex, index)" type="button"><i
                                      class="bi bi-trash"></i></button>
                                </div>
                                <img :src="thumbnail(image)" class="img-fluid" style="max-height: 40px;">
                                <input type="hidden" class="form-control" v-model="sku.images[index]"
                                        :name="'skus[' + skuIndex + '][images][]'" placeholder="image">
                              </div>
                              <div
                                class="border d-flex justify-content-center align-items-center border-dashed bg-light wh-40"
                                role="button" @click="addProductImages('image', skuIndex)"><i
                                  class="bi bi-plus fs-3 text-muted"></i></div>
                            </div>
                            <input type="hidden" class="form-control" :name="'skus[' + skuIndex + '][is_default]'"
                                    :value="skuIndex == 0 ? 1 : 0">
                            <input v-for="(variantValueIndex, j) in sku.variants" type="hidden"
                                    :name="'skus[' + skuIndex + '][variants][' + j + ']'" :value="variantValueIndex">
                          </td>
                          <td><input type="text" class="form-control" v-model="sku.model"
                                      :name="'skus[' + skuIndex + '][model]'"
                                      placeholder="{{ __('admin/product.model') }}"></td>
                          <td>
                            <input type="text" :class="['form-control', sku.sku_error ? 'is-invalid' : '']"
                                    v-model="sku.sku" :name="'skus[' + skuIndex + '][sku]'" placeholder="sku"
                                    :style="sku.is_default ? 'margin-top: 19px;' : ''" required>
                            <span role="alert" class="invalid-feedback"
                                  v-if="sku.sku_error">{{ __('admin/product.sku_error_repeat', ['name' => 'sku']) }}</span>
                            <span role="alert" class="invalid-feedback"
                                  v-else>{{ __('common.error_required', ['name' => 'sku']) }}</span>
                            <span v-if="sku.is_default * 1"
                                  class="text-success">{{ __('admin/product.default_main_product') }}</span>
                          </td>
                          <td>
                            <input type="number" class="form-control" v-model="sku.price"
                                    :name="'skus[' + skuIndex + '][price]'" step="any"
                                    placeholder="{{ __('admin/product.price') }}" required>
                            <span role="alert"
                                  class="invalid-feedback">{{ __('common.error_required', ['name' => __('admin/product.price')]) }}</span>
                            @hook('admin.product.edit.sku.price.tiered')
                          </td>
                          <td><input type="number" class="form-control" v-model="sku.origin_price"
                                      :name="'skus[' + skuIndex + '][origin_price]'" step="any"
                                      placeholder="{{ __('admin/product.origin_price') }}">
                            <span role="alert"
                                  class="invalid-feedback">{{ __('common.error_required', ['name' => __('admin/product.origin_price')]) }}</span>
                          </td>
                          <td><input type="number" class="form-control" v-model="sku.cost_price"
                                      :name="'skus[' + skuIndex + '][cost_price]'" step="any"
                                      placeholder="{{ __('admin/product.cost_price') }}">
                          </td>
                          <td><input type="number" class="form-control" v-model="sku.quantity"
                                      :name="'skus[' + skuIndex + '][quantity]'"
                                      placeholder="{{ __('admin/product.quantity') }}"></td>
                          <td><input type="number" class="form-control" v-model="sku.weight" step="any" :name="'skus[' + skuIndex + '][weight]'" placeholder="{{ __('admin/product.weight_text') }}"></td>
                          <td>
                            <select class="form-select" :disabled="sku.is_default" v-model="sku.active" :name="'skus[' + skuIndex + '][active]'">
                              <option value="1">{{ __('common.enable') }}</option>
                              <option value="0">{{ __('common.disable') }}</option>
                            </select>
                          </td>
                          @hook('admin.product.edit.sku.variants.after')
                        </tr>
                        </tbody>
                      </table>
                      <div class="help-text font-size-12 lh-base">{{ __('admin/product.sku_hint') }}</div>
                    </div>
                    @endhookwrapper
                  </div>
                  <input class="form-control d-none" :value="skuIsEmpty" required>
                  <div class="invalid-feedback" style="font-size: 16px"><i
                      class="bi bi-exclamation-circle-fill"></i> {{ __('admin/product.add_variable') }}</div>
                </div>
              </div>
            </div>

            @hook('admin.product.form.main.card.variable.after')
          </div>
        </div>

        <div class="card mb-3 seo-wrap pf-card">
          <div class="card-body">
            <div class="pf-head">
              <span class="pf-head__idx">5</span>
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_seo') }}</h5>
                <p class="pf-head__sub">{{ __('admin/product.section_seo_desc') }}</p>
              </div>
            </div>
            @hook('admin.product.seo.before')

            <x-admin-form-input-locale name="descriptions.*.meta_title" title="Meta title" :value="$descriptions"/>
            <x-admin::form.row title="Meta keywords">
              <div class="input-locale-wrap">
                @foreach ($languages as $language)
                  <div class="input-group">
                    <span class="input-group-text">{{ $language['name'] }}</span>
                    <textarea rows="2" type="text" name="descriptions[{{ $language['code'] }}][meta_keywords]"
                              class="form-control input-{{ $language['code'] }} "
                              placeholder="Meta keywords">{{ old('descriptions.' . $language['code'] . '.meta_keywords', $product->descriptions->keyBy('locale')[$language->code]->meta_keywords ?? '') }}</textarea>
                  </div>
                @endforeach
                @include('admin::shared.auto-translation')
              </div>
            </x-admin::form.row>
            <x-admin::form.row title="Meta description">
              <div class="input-locale-wrap">
                @foreach ($languages as $language)
                  <div class="input-group">
                    <span class="input-group-text wp-100">{{ $language['name'] }}</span>
                    <textarea rows="2" type="text" name="descriptions[{{ $language['code'] }}][meta_description]"
                              class="form-control input-{{ $language['code'] }} "
                              placeholder="Meta description">{{ old('descriptions.' . $language['code'] . '.meta_description', $product->descriptions->keyBy('locale')[$language->code]->meta_description ?? '') }}</textarea>
                  </div>
                @endforeach
                @include('admin::shared.auto-translation')
              </div>
            </x-admin::form.row>

            @hook('admin.product.seo.after')
          </div>
        </div>

        @hook('admin.product.form.main.after')
      </div>
      <div class="col-lg-3 pf-sidebar">
        @hook('admin.product.form.sidebar.before')

        <div class="card mb-3 pf-card">
          <div class="card-body pb-0">
            <div class="pf-head">
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_publish') }}</h5>
                <p class="pf-head__sub">{{ __('admin/product.publish_hint') }}</p>
              </div>
            </div>

            @hook('admin.product.form.sidebar.card.status.before')

            <x-admin-form-switch name="active" title="" :value="old('active', $product->active ?? 1)"/>

            @hook('admin.product.form.sidebar.card.status.after')
          </div>
        </div>

        <div class="card mb-3 pf-card">
          <div class="card-body">
            <div class="pf-head">
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_category') }}</h5>
              </div>
            </div>

            @hook('admin.product.form.sidebar.card.category.before')

            @hookwrapper('admin.product.edit.category')
              <el-cascader
                :options="source.flattenCategories"
                size="small"
                class="w-100"
                ref="refCascader"
                placeholder="{{ __('admin/product.category_placeholder') }}"
                :class="['category-cascader', !form.categories.length ? 'no-data' : '']"
                :props="{ label: 'name', value: 'id', children: 'children', checkStrictly: true}"
                @change="categoriesChange" filterable></el-cascader>
              <div class=" form-control category-data" v-if="categoryFormat.length">
                <div v-for="item, index in categoryFormat" :key="index" class="d-flex align-items-center">
                  <div class="me-2 cursor-pointer delete-icon" @click="form.categories.splice(index, 1)"><i
                      class="bi bi-dash-circle"></i></div>
                  <div class="category-name">@{{ item.name }}</div>
                  <input type="hidden" name="categories[]" :value="item.id">
                </div>
              </div>
            @endhookwrapper

            @hook('admin.product.form.sidebar.card.category.after')
          </div>
        </div>

        <div class="card mb-3 pf-card">
          <div class="card-body">
            <div class="pf-head">
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_logistics') }}</h5>
              </div>
            </div>

            @hook('admin.product.form.sidebar.card.data.before')

            <x-admin::form.row :title="__('admin/product.weight_text')">
              <div class="d-flex">
                <input type="text" name="weight" placeholder="{{ __('admin/product.weight_text') }}"
                        value="{{ old('weight', $product->weight ?? '') }}" class="form-control flex-1" />
                <x-admin-form-select
                  name="weight_class"
                  class="form-select ms-3"
                  style="flex: 0 0 100px"
                  :options="$weight_classes"
                  :value="$product->weight_class ?? $system_weight"
                  label="echo __('product.' . $option);"
                  format="0"
                />
              </div>
            </x-admin::form.row>

            @hookwrapper('admin.product.edit.brand')
            <x-admin::form.row :title="__('admin/brand.index')">
              <input type="text" name="brand_name" value="{{ old('brand_name', $product->brand->name ?? '') }}"
                      placeholder="{{ __('admin/builder.modules_keywords_search') }}" id="brand-autocomplete"
                      class="form-control  "/>
              <input type="hidden" name="brand_id" value="{{ old('brand_id', $product->brand_id ?? '') }}"/>
            </x-admin::form.row>
            @endhookwrapper
            <x-admin-form-select :title="__('admin/tax_class.index')" name="tax_class_id" :value="old('tax_class_id', $product->tax_class_id ?? '')" :options="$tax_classes" key="id" label="title"/>
            <x-admin-form-select name="shipping" :title="__('admin/common.shipping')" :value="old('shipping', $product->shipping ?? 1)" :options="[['title' => __('common.yes'), 'id' => 1], ['title' => __('common.no'),'id' => 0]]" key="id" label="title"/>

            @hook('admin.product.edit.extra')
          </div>
        </div>

        <div class="card mb-3 pf-card">
          <div class="card-body">
            <div class="pf-head">
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_attr') }}</h5>
              </div>
            </div>

            @hook('admin.product.form.sidebar.card.attribute.before')

            @hookwrapper('admin.product.edit.attribute')
              <table class="table table-bordered">
                <thead>
                <th>{{ __('admin/attribute.index') }}</th>
                <th>{{ __('admin/attribute.attribute_value') }}</th>
                <th width="50px"></th>
                </thead>
                <tbody>
                <tr v-for="item, index in form.attributes" :key="index">
                  <td>
                    <el-autocomplete
                      v-model="item.attribute.name"
                      :fetch-suggestions="attributeQuerySearch"
                      placeholder="{{ __('admin/builder.modules_keywords_search') }}"
                      value-key="name"
                      class="w-100"
                      size="small"
                      @select="(e) => {attributeHandleSelect(e, index, 'attribute')}"
                    ></el-autocomplete>
                    <input type="text" required :name="'attributes['+ index +'][attribute_id]'"
                            v-model="item.attribute.id" class="form-control d-none">
                    <div
                      class="invalid-feedback">{{ __('common.error_required', ['name' => __('admin/attribute.index')]) }}</div>
                  </td>
                  <td>
                    <el-autocomplete
                      v-model="item.attribute_value.name"
                      :fetch-suggestions="((query, cb) => {attributeValueQuerySearch(query, cb, index)})"
                      size="small"
                      :disabled="item.attribute.id == ''"
                      value-key="name"
                      class="w-100"
                      :placeholder="item.attribute.id == '' ? '{{ __('admin/attribute.before_attribute') }}' : '{{ __('admin/builder.modules_keywords_search') }}'"
                      @select="(e) => {attributeHandleSelect(e, index, 'attribute_value')}"
                    ></el-autocomplete>
                    <input type="text" required :name="'attributes['+ index +'][attribute_value_id]'"
                            v-model="item.attribute_value.id" class="form-control d-none">
                    <div
                      class="invalid-feedback">{{ __('common.error_required', ['name' => __('admin/attribute.attribute_value')]) }}</div>
                  </td>
                  <td class="text-end">
                    <i @click="form.attributes.splice(index, 1)"
                        class="bi bi-x-circle fs-4 text-danger cursor-pointer"></i>
                  </td>
                </tr>
                <tr>
                  <td colspan="2"></td>
                  <td class="text-end"><i class="bi bi-plus-circle cursor-pointer fs-4" @click="addAttribute"></i>
                  </td>
                </tr>
                </tbody>
              </table>
            @endhookwrapper

            @hook('admin.product.form.sidebar.card.attribute.after')
          </div>
        </div>

        <div class="card mb-3 pf-card">
          <div class="card-body">
            <div class="pf-head">
              <div>
                <h5 class="pf-head__title">{{ __('admin/product.section_relations') }}</h5>
              </div>
            </div>

            @hook('admin.product.form.sidebar.card.relations.before')

            <div class="module-edit-group">
              <div class="autocomplete-group-wrapper">
                <el-autocomplete
                  class="inline-input"
                  v-model="relations.keyword"
                  popper-class="product-autocomplete-list"
                  value-key="name"
                  size="small"
                  :fetch-suggestions="relationsQuerySearch"
                  placeholder="{{ __('admin/builder.modules_keywords_search') }}"
                  @select="relationsHandleSelect"
                >
                <template slot-scope="{ item }">
                  <div class="product-item">
                    <div class="image"><img :src="item.image_format" class="img-fluid"></div>
                    <div class="name" v-text="item.name"></div>
                  </div>
                </template>
                </el-autocomplete>

                <div class="item-group-wrapper" v-loading="relations.loading" v-if="relations.products.length">
                  <draggable
                    ghost-class="dragabble-ghost"
                    :list="relations.products"
                    :animation="330"
                  >
                    <div v-for="(item, index) in relations.products" :key="index" class="item">
                      <div class="product-item">
                        <div class="image"><img :src="item.image_format || (item.images && item.images[0]) || ''" class="img-fluid"></div>
                        <span>@{{ item.name }}</span>
                      </div>
                      <i class="el-icon-delete right" @click="relationsRemoveProduct(index)"></i>
                      <input type="text" :name="'relations['+ index +']'" v-model="item.id"
                              class="form-control d-none">
                    </div>
                  </draggable>
                </div>
              </div>
            </div>

            @hook('admin.product.form.sidebar.card.relations.after')
          </div>
        </div>

        @hook('admin.product.form.sidebar.after')
      </div>
    </div>

    @hook('admin.product.form.content.after')

    <x-admin::form.row title="">
      <button type="submit" class="btn btn-primary btn-submit mt-3 btn-lg">{{ __('common.save') }}</button>
    </x-admin::form.row>

    <el-dialog
      title="{{ __('common.edit') }}"
      :visible.sync="dialogVariables.show"
      width="400"
      @close="closedialogVariablesFormDialog('form')"
      :close-on-click-modal="false">
      <el-form ref="form" :rules="rules" :model="dialogVariables.form" label-width="100px">
        <el-form-item label="{{ __('common.name') }}" required class="language-inputs">
          <el-form-item :prop="'name.' + lang.code" :inline-message="true" v-for="lang, lang_i in source.languages" :key="lang_i" :rules="[
              { required: true, message: '{{ __('common.error_input_required') }}', trigger: 'blur' },
            ]">
            <el-input size="mini" v-model="dialogVariables.form.name[lang.code]"
                      placeholder="{{ __('common.name') }}">
              <template slot="prepend">@{{lang.name}}</template>
            </el-input>
          </el-form-item>
          @hook('admin.product.sku.edit.item.after')
        </el-form-item>
        @hook('admin.product.sku.edit.item.btn.before')
        <el-form-item>
          <el-button type="primary" @click="dialogVariablesFormSubmit('form')">{{ __('common.save') }}</el-button>
          <el-button @click="closedialogVariablesFormDialog('form')">{{ __('common.cancel') }}</el-button>
        </el-form-item>
      </el-form>
    </el-dialog>

    <el-dialog title="{{ __('admin/attribute.attribute_value') }}" :visible.sync="attributeDialog.show"
                width="670px"
                @close="attributeCloseDialog('attribute_form')" :close-on-click-modal="false">

      <el-form ref="attribute_form" :model="attributeDialog.form" label-width="155px">
        <el-form-item label="{{ __('common.name') }}" required class="language-inputs">
          <el-form-item :prop="'name.' + lang.code" :inline-message="true" v-for="lang, lang_i in source.languages"
                        :key="lang_i"
                        :rules="[
              { required: true, message: '{{ __('common.error_required', ['name' => __('common.name')]) }}', trigger: 'blur' },
            ]"
          >
            <el-input size="mini" v-model="attributeDialog.form.name[lang.code]"
                      placeholder="{{ __('common.name') }}">
              <template slot="prepend">@{{lang.name}}</template>
            </el-input>
          </el-form-item>
        </el-form-item>

        <el-form-item>
          <div class="d-flex d-lg-block mt-4">
            <el-button type="primary" @click="attributeSubmit('attribute_form')">{{ __('common.save') }}</el-button>
            <el-button @click="attributeCloseDialog('attribute_form')">{{ __('common.cancel') }}</el-button>
          </div>
        </el-form-item>
      </el-form>
    </el-dialog>

    <el-dialog
      title="{{ __('admin/product.add_url') }}"
      :visible.sync="addUrlDialog.show"
      width="400">
      <el-form ref="addUrlForm" :model="addUrlDialog" :rules="addUrlDialog.rules" label-width="100px">
        <el-form-item label="{{ __('admin/product.add_url') }}" required prop="url">
          <el-input v-model="addUrlDialog.url" placeholder="{{ __('admin/product.add_url') }}"></el-input>
          <p class="text-muted mb-0">{{ __('admin/product.add_url_help') }}</p>
        </el-form-item>
        <el-form-item>
          <div class="d-flex d-lg-block">
            <el-button type="primary" @click="addUrlSubmit('addUrlForm')">{{ __('common.save') }}</el-button>
            <el-button @click="addUrlDialog.show = false">{{ __('common.cancel') }}</el-button>
          </div>
        </el-form-item>
      </el-form>
    </el-dialog>
  </form>

  @hook('admin.product.form.footer')
@endsection

@push('footer')
  <link rel="stylesheet" href="{{ asset('beike/admin/vendor/cropper/cropper.min.css') }}">
  <script src="{{ asset('beike/admin/vendor/cropper/cropper.min.js') }}"></script>
  <script>
    // 商品详情图：上传、裁切、排序、实时预览
    (function () {
      function boot() {
        var root = document.getElementById('pdim');
        if (!root) return;

      var uploadUrl = root.getAttribute('data-upload-url');
      var hiddenInput = document.getElementById('pdim-input');
      var listEl = document.getElementById('pdim-list');
      var emptyEl = document.getElementById('pdim-empty');
      var statusEl = document.getElementById('pdim-status');
      var fileInput = document.getElementById('pdim-file');
      var uploadBox = document.getElementById('pdim-upload');
        var modal = document.getElementById('pdim-modal');
      var cropImage = document.getElementById('pdim-crop-image');
      var csrfMeta = document.querySelector('meta[name="csrf-token"]');
      var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

      var MAX_SIZE = 5 * 1024 * 1024;
      var ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
      var items = [];
      var draggingIndex = null;
      var cropper = null;
      var editingIndex = -1;

      try {
        items = JSON.parse(root.getAttribute('data-items') || '[]') || [];
      } catch (e) {
        items = [];
      }
      items = items.map(function (item) {
        return { image: item.image, crop: item.crop || null, preview: null };
      });

      function setStatus(message, type) {
        if (!message) {
          statusEl.hidden = true;
          statusEl.textContent = '';
          return;
        }
        statusEl.hidden = false;
        statusEl.className = 'pdim__status pdim__status--' + type;
        statusEl.textContent = message;
      }

      function imageUrl(path) {
        return path.indexOf('http') === 0 ? path : '/' + path.replace(/^\/+/, '');
      }

      function syncInput() {
        hiddenInput.value = JSON.stringify(items.map(function (item, index) {
          return { image: item.image, crop: item.crop, position: index };
        }));
        emptyEl.hidden = items.length > 0;
      }

      function render() {
        listEl.innerHTML = '';
        items.forEach(function (item, index) {
          var wrap = document.createElement('div');
          wrap.className = 'pdim-item';
          wrap.draggable = true;
          wrap.dataset.index = index;
          wrap.innerHTML =
            '<div class="pdim-item__thumb"><img src="' + (item.preview || imageUrl(item.image)) + '" alt=""></div>' +
            '<span class="pdim-item__index">' + (index + 1) + '</span>' +
            (item.crop ? '<span class="pdim-item__badge">已裁切</span>' : '') +
            '<div class="pdim-item__actions">' +
              '<button type="button" data-action="crop">裁切</button>' +
              '<button type="button" data-action="up">↑</button>' +
              '<button type="button" data-action="down">↓</button>' +
              '<button type="button" class="is-danger" data-action="remove">删除</button>' +
            '</div>';
          listEl.appendChild(wrap);
        });
        syncInput();
      }

      function move(from, to) {
        if (to < 0 || to >= items.length) return;
        var moved = items.splice(from, 1)[0];
        items.splice(to, 0, moved);
        render();
      }

      listEl.addEventListener('click', function (event) {
        var button = event.target.closest('button[data-action]');
        if (!button) return;

        var index = parseInt(button.closest('.pdim-item').dataset.index, 10);
        var action = button.dataset.action;

        if (action === 'crop') {
          openCrop(index);
        } else if (action === 'remove') {
          items.splice(index, 1);
          render();
          setStatus('已删除 1 张图片', 'success');
        } else if (action === 'up') {
          move(index, index - 1);
        } else if (action === 'down') {
          move(index, index + 1);
        }
      });

      listEl.addEventListener('dragstart', function (event) {
        var item = event.target.closest('.pdim-item');
        if (!item) return;
        draggingIndex = parseInt(item.dataset.index, 10);
        item.classList.add('is-dragging');
      });

      listEl.addEventListener('dragend', function (event) {
        var item = event.target.closest('.pdim-item');
        if (item) item.classList.remove('is-dragging');
        draggingIndex = null;
      });

      listEl.addEventListener('dragover', function (event) {
        if (draggingIndex !== null) event.preventDefault();
      });

      listEl.addEventListener('drop', function (event) {
        if (draggingIndex === null) return;
        var target = event.target.closest('.pdim-item');
        if (!target) return;

        event.preventDefault();
        var to = parseInt(target.dataset.index, 10);
        if (to !== draggingIndex) move(draggingIndex, to);
        draggingIndex = null;
      });

      function compressImage(file) {
        return new Promise(function (resolve) {
          if (file.size <= MAX_SIZE) {
            resolve(file);
            return;
          }

          var objectUrl = URL.createObjectURL(file);
          var image = new Image();

          image.onload = function () {
            var maxEdge = 4000;
            var scale = Math.min(1, maxEdge / Math.max(image.width, image.height));
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(image.width * scale);
            canvas.height = Math.round(image.height * scale);
            canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(objectUrl);

            canvas.toBlob(function (blob) {
              if (!blob) {
                resolve(file);
                return;
              }

              resolve(new File([blob], file.name.replace(/\.(png|webp|jpe?g)$/i, '.jpg'), { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.92);
          };

          image.onerror = function () {
            URL.revokeObjectURL(objectUrl);
            resolve(file);
          };

          image.src = objectUrl;
        });
      }

      function uploadFiles(files) {
        var pending = Array.prototype.slice.call(files || []);
        if (!pending.length) return;

        var valid = [];
        var errors = [];

        pending.forEach(function (file) {
          if (ALLOWED.indexOf(file.type) === -1) {
            errors.push(file.name + '：格式不支持');
          } else {
            valid.push(file);
          }
        });

        if (!valid.length) {
          setStatus(errors.join('；'), 'error');
          return;
        }

        var total = valid.length;
        var done = 0;

        function finish() {
          var message = '上传完成，共新增 ' + done + ' 张';
          if (errors.length) message += '；' + errors.join('；');
          setStatus(message, errors.length ? 'error' : 'success');
        }

        function upload(file) {
          var form = new FormData();
          form.append('file', file, file.name);
          form.append('path', '/products');

          return fetch(uploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: form,
            credentials: 'same-origin'
          })
            .then(function (response) { return response.json(); })
            .then(function (json) {
              var path = json.path || (json.data && json.data.path);
              if (path) {
                items.push({ image: path, crop: null, preview: null });
                done++;
                render();
              } else {
                errors.push(file.name + '：' + (json.message || '上传失败'));
              }
            })
            .catch(function () {
              errors.push(file.name + '：网络异常');
            });
        }

        function next() {
          if (!valid.length) {
            finish();
            return;
          }

          var file = valid.shift();
          setStatus('正在处理 ' + (done + 1) + '/' + total + '：' + file.name, 'info');

          compressImage(file).then(function (prepared) {
            if (prepared.size > MAX_SIZE) {
              errors.push(file.name + '：压缩后仍超过 5MB');
              next();
              return;
            }

            upload(prepared).then(next);
          });
        }

        next();
      }

      var libraryButton = document.getElementById('pdim-library');
      if (libraryButton) {
        libraryButton.addEventListener('click', function () {
          if (!window.bk || typeof bk.fileManagerIframe !== 'function') {
            setStatus('图库组件未加载，请刷新页面重试', 'error');
            return;
          }

          bk.fileManagerIframe(function (images) {
            if (!images || !images.length) return;

            images.forEach(function (image) {
              if (image && image.path) {
                items.push({ image: image.path, crop: null, preview: null });
              }
            });

            render();
            setStatus('已从图库添加 ' + images.length + ' 张图片', 'success');
          }, { mime: 'image' });
        });
      }

      uploadBox.addEventListener('click', function () { fileInput.click(); });
      fileInput.addEventListener('change', function () {
        uploadFiles(fileInput.files);
        fileInput.value = '';
      });

      ['dragenter', 'dragover'].forEach(function (name) {
        uploadBox.addEventListener(name, function (event) {
          event.preventDefault();
          uploadBox.classList.add('is-dragover');
        });
      });

      ['dragleave', 'drop'].forEach(function (name) {
        uploadBox.addEventListener(name, function (event) {
          event.preventDefault();
          uploadBox.classList.remove('is-dragover');
        });
      });

      uploadBox.addEventListener('drop', function (event) {
        uploadFiles(event.dataTransfer.files);
      });

      function openCrop(index) {
        editingIndex = index;
        modal.hidden = false;

        var url = imageUrl(items[index].image);
        cropImage.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();

        cropImage.onload = function () {
          if (cropper) {
            cropper.destroy();
            cropper = null;
          }

          cropper = new Cropper(cropImage, {
            aspectRatio: NaN,
            viewMode: 1,
            autoCropArea: 0.9,
            background: false,
            responsive: true,
            movable: true,
            zoomable: true,
            scalable: true,
            rotatable: false,
            checkOrientation: false,
            ready: function () {
              if (items[index].crop) {
                cropper.setData(items[index].crop);
              }
            }
          });
        };
      }

      function closeCrop() {
        modal.hidden = true;
        if (cropper) {
          cropper.destroy();
          cropper = null;
        }
        editingIndex = -1;
      }

      modal.addEventListener('click', function (event) {
        if (event.target.hasAttribute('data-pdim-close')) closeCrop();
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) closeCrop();
      });

      document.getElementById('pdim-reset').addEventListener('click', function () {
        if (cropper) cropper.reset();
      });

      document.getElementById('pdim-apply').addEventListener('click', function () {
        if (!cropper || editingIndex < 0) return;

        var data = cropper.getData(true);
        var ratio = data.width > 0 ? data.height / data.width : 1;
        var canvas = cropper.getCroppedCanvas({
          width: 400,
          height: Math.max(1, Math.round(400 * ratio)),
          imageSmoothingQuality: 'high'
        });

        items[editingIndex].crop = {
          x: Math.round(data.x),
          y: Math.round(data.y),
          width: Math.round(data.width),
          height: Math.round(data.height)
        };
        items[editingIndex].preview = canvas ? canvas.toDataURL('image/jpeg', 0.85) : null;

        closeCrop();
        render();
        setStatus('裁切已更新，保存商品后生效', 'success');
      });

        render();
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
      } else {
        setTimeout(boot, 0);
      }
    })();
  </script>
  <script>
    @hook('admin.product.edit.script.before')

    // 商品描述富文本：编辑器内统一按正方形裁剪预览，与前台详情图一致
    (function () {
      var css = 'body img{width:100%!important;height:auto!important;aspect-ratio:1/1!important;object-fit:cover!important;object-position:center!important;display:block!important;margin:0 auto 8px!important;background:#eef0f3;border-radius:8px}body p{margin:0 0 8px!important}';

      function isDescriptionEditor(editor) {
        var el = editor && editor.getElement ? editor.getElement() : null;
        var name = el ? (el.getAttribute('name') || '') : '';

        return name.indexOf('descriptions[') === 0 && name.indexOf('[content]') !== -1;
      }

      function inject(editor) {
        if (!editor || !isDescriptionEditor(editor)) {
          return;
        }

        var apply = function () {
          try {
            var doc = editor.getDoc();
            if (!doc || doc.getElementById('pd-square-style')) {
              return;
            }
            var style = doc.createElement('style');
            style.id = 'pd-square-style';
            style.textContent = css;
            doc.head.appendChild(style);
          } catch (e) {
            // 忽略编辑器未就绪异常
          }
        };

        editor.on('init SetContent', apply);
        apply();
      }

      function bind() {
        if (!window.tinymce) {
          return false;
        }
        tinymce.on('AddEditor', function (e) { inject(e.editor); });
        (tinymce.editors || []).forEach(inject);

        return true;
      }

      if (!bind()) {
        document.addEventListener('DOMContentLoaded', bind);
      }
    })();

    $('.submit-form-edit, .submit-form').on('click', function () {
      submitBeforeFormat()
      if (!app.validateSku()) {
        return layer.msg('{{ __('admin/product.sku_error_repeat') }}', () => {
        });
      }

      if ($(this).hasClass('submit-form-edit')) {
        const action = $(`form#app`).attr('action');
        $(`form#app`).attr('action', bk.updateQueryStringParameter(action, 'action_type', 'stay'));
      }

      setTimeout(() => {
        $(`form#app`).find('button[type="submit"]')[0].click();
      }, 0);
    })

    function submitBeforeFormat() {
      // 关闭多规格提交 清空 variables
      if (!app.editing.isVariable) {
        app.source.variables = [];
      }

      // app.videoSubmitFormat()
    }

    var app = new Vue({
      el: '#app',
      data: {
        current_language_code: '{{ locale() }}',
        isMove: false,
        isVariableFullEdit: false,
        imagesHandle: {
          isDragOver: false,
          showAll: false,
        },
        form: {
          categories: @json(old('categories', $category_ids) ?? []),
          attributes: @json(old('pickups', $product_attributes) ?? []),
          images: @json(old('images', $product->images) ?? []),
          video: {
            path: @json(old('video', $product->video ?? '')),
            url: @json(image_origin(old('video', $product->video ?? ''))),
          },
          xhs_url: @json(old('xhs_url', $product->xhs_url ?? '')),
          taobao_url: @json(old('taobao_url', $product->taobao_url ?? '')),
          douyin_url: @json(old('douyin_url', $product->douyin_url ?? '')),
          model: @json($product->skus[0]['model'] ?? ''),
          price: @json($product->skus[0]['price'] ?? ''),
          quantity: @json($product->skus[0]['quantity'] ?? ''),
          sku: @json($product->skus[0]['sku'] ?? ''),
          status: @json($product->skus[0]['status'] ?? false),
          variables: [],
          skus: @json(old('skus', $product->skus) ?? []),
        },

        variablesBatch: {
          variables: [],
          model: '',
          sku: '',
          price: '',
          origin_price: '',
          cost_price: '',
          quantity: '',
          image: '',
          active: '',
        },

        relations: {
          keyword: '',
          products: @json($relations ?? []),
          loading: null,
        },

        source: {
          variables: [],
          languages: @json($languages ?? []),
          categories: @json($source['categories'] ?? []),
          flattenCategories: @json($source['flatten_categories'] ?? []),
        },

        editing: {
          isVariable: false,
        },

        dialogVariables: {
          show: false,
          variantIndex: null,
          variantValueIndex: null,
          form: {
            name: {}
          },
        },

        attributeDialog: {
          show: false,
          index: null,
          form: {
            name: {},
          }
        },

        addUrlDialog: {
          show: false,
          url: '',
          rules: {
            url: [
              { required: true, message: '{{ __('common.error_input_required') }}', trigger: 'blur' },
            ],
          },
        },

        rules: {},
        @stack('admin.product.edit.vue.data')
      },

      computed: {
        productImages() {
          if (this.imagesHandle.showAll) {
            return this.form.images;
          }
          return this.form.images.slice(0, 8);
        },

        // variant value 重复次数
        variantValueRepetitions() {
          var repeats = [];
          var repeat = 1;
          for (var index = this.form.variables.length - 2; index >= 0; index--) {
            repeat *= this.form.variables[index + 1].values.length;
            repeats[index] = repeat;
          }
          // 最后一组只重复1次
          repeats.push(1);
          return repeats;
        },

        skuIsEmpty() {
          return (this.form.skus.length && this.form.skus[0].variants.length) || ''
        },

        categoryFormat() {
          const categories = JSON.parse(JSON.stringify(this.source.categories));
          const categoryIds = this.form.categories;
          const categoryFormat = [];
          categoryIds.forEach((categoryId, index) => {
            const category = categories.find(v => v.id == categoryId);
            if (category) {
              categoryFormat.push(category);
            }
          })

          return categoryFormat;
        },

        @hook('admin.product.edit.vue.computed')
      },

      beforeMount() {
        let variables = @json(old('variables', $product->variables) ?? []);
        // 修复表单提交报错后，编辑好的规格不存在问题，old 返回的规格是字符串，variables 如果是字符串，需要转数组
        if (typeof variables === 'string') {
          variables = JSON.parse(variables);
        }

        this.form.variables = variables
        this.source.variables = variables
        this.editing.isVariable = variables.length > 0

        if (this.form.variables.length) {
          this.variablesBatch.variables = this.form.variables.map((v, i) => '');
        }

        @hook('admin.product.edit.vue.beforeMount')
      },

      watch: {
        'source.variables': {
          deep: true,
          handler: function (val) {
            // 原始规格数据变动，过滤有效规格并同步至 form.variables
            let variants = [];
            const sourceVariants = JSON.parse(JSON.stringify(this.source.variables));
            for (var i = 0; i < sourceVariants.length; i++) {
              let sourceVariant = sourceVariants[i];
              // 排除掉没有规格值的
              if (sourceVariant.values.length > 0) {
                variants.push(sourceVariant);
              }
            }

            this.form.variables = variants;
            // 在 variablesBatch.variables 生成对应的 variants 的index
            this.variablesBatch.variables = variants.map((v, i) => '');

            setTimeout(() => {$('select[name="weight_class"]').trigger('change')}, 200);

            if (this.isMove) return;
            this.remakeSkus();
          }
        },
        @hook('admin.product.edit.vue.watch')
      },

      methods: {
        showVideo(src) {
          if (this.isYouTubeVideoUrl(src)) {
            window.open(src, '_blank');
            return;
          }

          layer.open({
            type: 1,
            title: false,
            closeBtn: 1,
            shadeClose: true,
            area: ['500px', '500px'],
            skin: 'product-video-preview-layer',
            content: `
              <div class="product-video-preview-dialog">
                <video src="${src}" controls autoplay playsinline class="product-video-preview-player"></video>
              </div>
            `,
          });
        },

        draggableCheckMove(e) {
          const toIndex = e.relatedContext.index;
          const fromIndex = e.draggedContext.index;

          // 禁止任何操作发生在第7个位置（index == 7）
          if (!this.imagesHandle.showAll && (toIndex >= 7 || fromIndex >= 7)) {
            return false;
          }

          return true;
        },

        isVideo(src) {
          if (!src) return false;

          const lowerSrc = src.toLowerCase();

          return (
            /\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/.test(lowerSrc) ||
            lowerSrc.includes('youtube.com/watch?v=') ||
            lowerSrc.includes('youtu.be/') ||
            lowerSrc.includes('youtube.com/embed/') ||
            lowerSrc.includes('youtube.com/shorts/')
          );
        },

        isXhsNoteUrl(url) {
          if (!url) return false;

          const lowerUrl = String(url).toLowerCase();

          return lowerUrl.includes('xiaohongshu.com') || lowerUrl.includes('xhslink.com');
        },

        isYouTubeVideoUrl(url) {
          try {
            const parsed = new URL(url);
            const hostname = parsed.hostname.replace(/^www\./, '');

            // 标准 YouTube 视频链接
            if (hostname === 'youtube.com' && parsed.pathname === '/watch' && parsed.searchParams.has('v')) {
              return true;
            }

            if (hostname === 'youtube.com' && parsed.pathname.startsWith('/embed/')) {
              return true;
            }

            if (hostname === 'youtube.com' && parsed.pathname.startsWith('/shorts/')) {
              return true;
            }

            // 短链接 youtu.be
            if (hostname === 'youtu.be' && parsed.pathname.length > 1) {
              return true;
            }


            return false;
          } catch (e) {
            return false; // 非法 URL
          }
        },

        getYouTubeVideoId(url) {
          try {
            const parsed = new URL(url);
            const hostname = parsed.hostname.replace(/^www\./, '');

            if (hostname === 'youtu.be') {
              return parsed.pathname.slice(1);
            }

            if (hostname === 'youtube.com' && parsed.pathname === '/watch') {
              return parsed.searchParams.get('v');
            }

            if (hostname === 'youtube.com' && parsed.pathname.startsWith('/embed/')) {
              return parsed.pathname.split('/embed/')[1]?.split('/')[0] || null;
            }

            if (hostname === 'youtube.com' && parsed.pathname.startsWith('/shorts/')) {
              return parsed.pathname.split('/shorts/')[1]?.split('/')[0] || null;
            }
          } catch (e) {
            return null;
          }
          return null;
        },

        productsUploadNew() {
          $('#new-file-input').click();
        },

        newFileChange(e) {
          const files = Array.from(e.target.files);
          this.newProductUploadFilesApi(files);
        },

        handleDragOver(e) {
          const hasFiles = e.dataTransfer?.types?.includes?.("Files");
          if (hasFiles) {
            this.imagesHandle.isDragOver = true;
          }
        },

        handleDragLeave(e) {
          // 检查是否离开了整个容器，而不是进入子元素
          if (!e.currentTarget.contains(e.relatedTarget)) {
            this.imagesHandle.isDragOver = false
          }
        },

        onDrop(e) {
          this.imagesHandle.isDragOver = false;
          const files = Array.from(e.dataTransfer.files);
          this.newProductUploadFilesApi(files);
        },

        newProductUploadFilesApi(files) {
          const validFiles = files.filter(file =>
            file.type.startsWith('image/') || file.type.startsWith('video/')
          );

          if (validFiles.length > 0) {
            validFiles.forEach(file => {
              var formData = new FormData();
              formData.append("file", file, file.name);
              formData.append("path", '/products');

              $http.post('{{ admin_route('file_manager.upload') }}', formData, {hmsg: true}).then((res) => {
                if (this.isVideo(file.name)) {
                  this.form.video.path = res.path;
                  this.form.video.url = res.url;
                }

                const videoIndex = this.form.images.findIndex(e => this.isVideo(e));
                if (videoIndex > -1 && this.isVideo(res.path)) {
                  this.form.images[videoIndex] = res.path;
                } else {
                  this.form.images.push(res.path);
                }
              })
            })
          }
        },

        categoriesChange(e) {
          const last = e[e.length - 1];
          this.$nextTick(() => {
            this.$refs.refCascader.dropDownVisible = false
            // this.$refs.refCascader.$refs.panel.checkedValue = [];
            this.$refs.refCascader.$refs.panel.activePath = [];
            this.$refs.refCascader.$refs.panel.syncActivePath()
            this.$refs.refCascader.panel.clearCheckedNodes()
          });

          if (this.form.categories.find(v => v == last)) return layer.msg('{{ __('admin/product.category_already') }}');
          if (last) {
            if (this.source.categories.find(v => v.id == last)) {
              this.form.categories.push(last);
            } else {
              layer.msg('{{ __('admin/product.category_disabled') }}');
            }
          }
        },

        // 视频数据提交的时候格式化
        videoSubmitFormat() {
          if (this.form.video.videoType == 'iframe') {
            this.form.video.path = this.form.video.iframe;
          }

          if (this.form.video.videoType == 'custom') {
            this.form.video.path = this.form.video.custom;
          }
        },

        validateSku() {
          let skuList = [];
          let validate = true;
          this.form.skus.forEach((sku, index) => {
            if (skuList.includes(sku.sku)) {
              this.$set(this.form.skus[index], 'sku_error', true);
              validate = false;
            } else {
              this.$set(this.form.skus[index], 'sku_error', false);
            }
            skuList.push(sku.sku);
          })

          return validate;
        },

        deleteVideo() {
          this.form.video.path = ''
          this.form.video.url = ''
        },

        relationsQuerySearch(keyword, cb) {
          $http.get('products/autocomplete?name=' + encodeURIComponent(keyword), null, {hload: true}).then((res) => {
            cb(res.data);
          })
        },

        relationsHandleSelect(item) {
          if (!this.relations.products.find(v => v == item.id)) {
            this.relations.products.push(item);
          }
          this.relations.keyword = ""
        },

        relationsRemoveProduct(index) {
          this.relations.products.splice(index, 1);
        },

        isVariableChange(e) {
          // if (!e) {
          //   this.source.variables = [];
          // }
        },

        variantIsImage(e, index) {
          if (!e) {
            this.source.variables[index].values.forEach(v => {
              v.image = '';
            })
          }
        },

        batchSettingVariantImage() {
          bk.fileManagerIframe(images => {
            this.variablesBatch.image = images[0].path;
          })
        },

        addProductImageUrl() {
          this.addUrlDialog.show = true;
        },

        addUrlSubmit(formName) {
          this.$refs[formName].validate((valid) => {
            if (valid) {
              const addedUrl = this.addUrlDialog.url;

              // 仅视频地址或小红书笔记链接写入 video 字段，避免添加普通图片时覆盖已有视频
              if (this.isVideo(addedUrl) || this.isXhsNoteUrl(addedUrl)) {
                this.form.video.path = addedUrl;
                this.form.video.url = addedUrl;
              }

              // 小红书笔记链接仅保存到 video 字段，不写入图片列表
              if (this.isXhsNoteUrl(addedUrl)) {
                this.addUrlDialog.show = false;
                return;
              }

              const videoIndex = this.form.images.findIndex(e => this.isVideo(e));
              if (videoIndex > -1 && this.isVideo(this.addUrlDialog.url)) {
                this.form.images[videoIndex] = this.addUrlDialog.url;
              } else {
                this.form.images.push(this.addUrlDialog.url);
              }

              this.addUrlDialog.show = false;
            }
          })
        },

        // 根据 youtube url 获取视频封面
        getVideoCover(url) {
          const videoId = url.split('v=')[1];
          return 'https://img.youtube.com/vi/' + videoId + '/0.jpg';
        },

        addProductImages(type, skuIndex) {
          bk.fileManagerIframe(images => {
            if (!isNaN(skuIndex)) {
              if (this.form.skus[skuIndex].images === null) {
                this.form.skus[skuIndex].images = images.map(e => e.path)
              } else {
                this.form.skus[skuIndex].images.push(...images.map(e => e.path))
              }
              return;
            }

            images.forEach(image => {
              if (this.isVideo(image.path)) {
                this.form.video.path = image.path;
                this.form.video.url = image.url;
              }

              const videoIndex = this.form.images.findIndex(e => this.isVideo(e));
              if (videoIndex > -1 && this.isVideo(image.path)) {
                this.form.images[videoIndex] = image.path;
              } else {
                this.form.images.push(image.path);
              }
            })
          }, {mime: type})
        },

        addProductVideo() {
          bk.fileManagerIframe(images => {
            this.form.video.path = images[0].path
            this.form.video.url = images[0].url
          }, {mime: 'video'})
        },

        removeImages(index) {
          this.form.images.splice(index, 1)
        },

        removeSkuImages(variantIndex, index) {
          this.form.skus[variantIndex].images.splice(index, 1)
        },

        batchSettingVariant() {
          // 要修改的 skuIndex 下标
          let setSkuIndex = [];
          const skus = JSON.parse(JSON.stringify(this.form.skus));

          skus.forEach((sku, skuIndex) => {
            this.variablesBatch.variables.forEach((v, i) => {
              if (v === '') {
                // sku.variants 数据中 i 的值修改为 ‘’
                sku.variants[i] = '';
              }
            })

            if (this.variablesBatch.variables.toString() === sku.variants.toString()) {
              setSkuIndex.push(skuIndex);
            }
          })

          // 修改 skuIndex 下标对应的 sku
          setSkuIndex.forEach((index) => {
            if (this.variablesBatch.model) {
              this.form.skus[index].model = this.variablesBatch.model + '-' + (index + 1);
            }
            if (this.variablesBatch.sku) {
              this.form.skus[index].sku = this.variablesBatch.sku + '-' + (index + 1);
            }
            if (this.variablesBatch.image) {
              this.form.skus[index].images = [this.variablesBatch.image];
            }
            if (this.variablesBatch.price) {
              this.form.skus[index].price = this.variablesBatch.price;
            }
            if (this.variablesBatch.origin_price) {
              this.form.skus[index].origin_price = this.variablesBatch.origin_price;
            }
            if (this.variablesBatch.cost_price) {
              this.form.skus[index].cost_price = this.variablesBatch.cost_price;
            }
            if (this.variablesBatch.weight) {
              this.form.skus[index].weight = this.variablesBatch.weight;
            }
            if (this.variablesBatch.quantity) {
              this.form.skus[index].quantity = this.variablesBatch.quantity;
            }
            if (this.variablesBatch.active && !this.form.skus[index].is_default) {
              this.form.skus[index].active = this.variablesBatch.active;
            }
            @stack('admin.product.edit.vue.method.batchSettingVariant')
          })

          // this.variablesBatch 对象内除了 variables 之外的值都清空
          for (let key in this.variablesBatch) {
            if (key !== 'variables') {
              this.variablesBatch[key] = '';
            }
          }
        },

        dialogVariablesFormSubmit(form) {
          const name = JSON.parse(JSON.stringify(this.dialogVariables.form.name));
          const variantIndex = this.dialogVariables.variantIndex;
          const variantValueIndex = this.dialogVariables.variantValueIndex;

          this.$refs[form].validate((valid) => {
            if (!valid) {
              this.$message.error('{{ __('common.error_form') }}');
              return;
            }

            if (variantValueIndex !== null) {
              if (variantValueIndex == -1) { // 创建
                this.source.variables[variantIndex].values.push({name, image: ''});
              } else {
                this.source.variables[variantIndex].values[variantValueIndex].name = name;
              }
            } else {
              if (variantIndex == -1) { // 创建
                this.source.variables.push({name, values: [], isImage: false});
              } else {
                this.source.variables[variantIndex].name = name;
              }
            }

            @stack('admin.product.edit.vue.method.dialogVariablesFormSubmit')

            this.dialogVariables.show = false;
          });
        },

        closedialogVariablesFormDialog(form) {
          this.dialogVariables.show = false;
          this.dialogVariables.variantIndex = null;
          this.dialogVariables.variantValueIndex = null;
          this.dialogVariables.form.name = {};
          this.$refs[form].clearValidate();
        },

        removeSourceVariantValue(variantIndex, variantValueIndex) {
          this.source.variables[variantIndex].values.splice(variantValueIndex, 1);
          // 找出 this.form.skus 中 variants[variantIndex] === variantValueIndex 的 sku，删除
          this.form.skus = this.form.skus.filter(sku => sku.variants[variantIndex] * 1 !== variantValueIndex * 1);

          // 根据现在的 this.source.variables values 重新生成迪卡尔积 ['0,0', '0,1']...
          const variants = this.source.variables.map(e => e.values.map((v, i) => i));
          const cartesian = this.cartesian(...variants);

          // 用 cartesian 跟新 this.form.skus 中的 variants
          cartesian.forEach((c, i) => {
            c = !Array.isArray(c) ? [c] : c;
            this.form.skus[i].variants = c.map(e => e + '');
          })
        },

        cartesian(...args) {
          if (args.length < 2) return args[0] || [];
          return [].reduce.call(args, (col, set) => {
            let res = [];
            col.forEach(c => {
              set.forEach(s => {
                let t = [].concat(Array.isArray(c) ? c : [c]);
                t.push(s);
                res.push(t);
              });
            });
            return res;
          });
        },

        modalVariantOpenButtonClicked(variantIndex, variantValueIndex) {
          this.dialogVariables.variantIndex = variantIndex;
          this.dialogVariables.variantValueIndex = variantValueIndex;

          let name = null;
          if (variantIndex === -1 || variantValueIndex === -1) {
            name = {};
          } else {
            if (variantValueIndex !== null) {
              // 编辑 variant value
              name = this.source.variables[variantIndex].values[variantValueIndex].name;
            } else {
              // 编辑 variant
              name = this.source.variables[variantIndex].name;
            }
          }

          this.dialogVariables.form.name = JSON.parse(JSON.stringify(name));
          @stack('admin.product.edit.vue.method.modalVariantOpenButtonClicked')
          this.dialogVariables.show = true;
        },

        removeSourceVariant(variantIndex) {
          this.source.variables.splice(variantIndex, 1);
          if (!this.source.variables.length) {
            setTimeout(() => { // 等 remakeSkus 完成 this.form.skus = [];
              this.form.skus = [{
                product_sku_id: 0,
                position: 1,
                variants: [],
                image: '',
                model: '',
                sku: '',
                price: null,
                quantity: null,
                is_default: 1,
                active: '',
              }];
              this.editing.isVariable = false;
            }, 0);
          }
        },

        addVariantValue(variantIndex) {
          this.dialogVariables.show = true;
          this.dialogVariables.type = 'variant-value';
          this.dialogVariables.variantIndex = variantIndex;
        },

        addAttribute() {
          this.form.attributes.push({attribute: {id: '', name: ''}, attribute_value: {id: '', name: ''}})
        },

        attributeQuerySearch(keyword, cb) {
          $http.get('attributes/autocomplete?name=' + encodeURIComponent(keyword), null, {hload: true}).then((res) => {
            cb(res.data);
          })
        },

        attributeValueQuerySearch(keyword, cb, index) {
          $http.get(`attributes/${this.form.attributes[index].attribute.id}/values/autocomplete?name=${encodeURIComponent(keyword)}`, null, {hload: true}).then((res) => {
            res.data.push({id: 'add', name: '{{ __('admin/attribute.add_attribute') }}'})
            cb(res.data);
          })
        },

        randomValue(len, type) {
          len = len || 32;  // 默认长度为 32
          var chars;

          // 根据 type 选择字符集
          switch (type) {
            case 'number':
              chars = '0123456789';
              break;
            case 'string':
            default:
              chars = 'abcdefghijklmnopqrstuvwxyz';
              break;
          }

          var maxPos = chars.length;
          var value = '';
          for (let i = 0; i < len; i++) {
            value += chars.charAt(Math.floor(Math.random() * maxPos));
          }
          return value;
        },

        attributeHandleSelect(item, index, type) {
          if (type == 'attribute' && item.id != this.form.attributes[index].attribute.id) {
            this.form.attributes[index].attribute_value.name = ''
            this.form.attributes[index].attribute_value.id = ''
          }

          if (item.id == 'add') {
            this.attributeDialog.show = true;
            this.attributeDialog.index = index;
            this.form.attributes[index].attribute_value.name = ''
            return;
          }

          this.form.attributes[index][type].name = item.name
          this.form.attributes[index][type].id = item.id
        },

        attributeSubmit(form) {
          const self = this;

          this.$refs[form].validate((valid) => {
            if (!valid) {
              this.$message.error('{{ __('common.error_form') }}');
              return;
            }

            const id = this.form.attributes[this.attributeDialog.index].attribute.id;

            $http.post(`attributes/${id}/values`, this.attributeDialog.form).then((res) => {
              this.form.attributes[this.attributeDialog.index].attribute_value.id = res.data.id
              this.form.attributes[this.attributeDialog.index].attribute_value.name = res.data.description.name
              this.attributeDialog.show = false
            })
          });
        },

        attributeCloseDialog(form) {
          this.$refs[form].resetFields();
          this.attributeDialog.form.name = {}
          this.attributeDialog.show = false
        },

        remakeSkus() {
          const combos = makeVariableIndexes();

          if (combos.length < 1) {
            this.form.skus = [];
            return;
          }

          // 找出已存在的组合
          const productVariantCombos = this.form.skus.map(v => v.variants.join()); // ['0,0,0', '0,0,1']
          let skus = [];
          for (var i = 0; i < combos.length; i++) {
            const combo = combos[i]; // 0,0,0
            const index = productVariantCombos.indexOf(combo.join());
            if (index > -1) {
              skus.push(this.form.skus[index]);
            } else {
              skus.push({
                product_sku_id: 0,
                position: i,
                variants: combo,
                images: [],
                model: '',
                sku: 'bksku-' + this.randomValue(4, 'string') + '-' + this.randomValue(3, 'number'),
                price: null,
                weight: $('input[name="weight"]').val(),
                quantity: null,
                is_default: i == 0,
                active: 1,
              });
            }
          }

          @stack('admin.product.edit.vue.method.remakeSkus')
          this.form.skus = skus;
        },

        // 规格值拖拽
        swapSourceVariantValue(e, variantIndex) {
          this.form.skus.forEach(function (sku) {
            const oldIndex = parseInt(sku.variants[variantIndex]);
            if (oldIndex == e.oldIndex) {
              sku.variants[variantIndex] = e.newIndex.toString();
            } else if (oldIndex > e.oldIndex && oldIndex <= e.newIndex) {
              sku.variants[variantIndex] = (oldIndex - 1).toString();
            } else if (oldIndex < e.oldIndex && oldIndex >= e.newIndex) {
              sku.variants[variantIndex] = (oldIndex + 1).toString();
            } else {
              sku.variants[variantIndex] = oldIndex.toString();
            }
          });

          this.remakeSkus()
        },

        videoTypeChange(code) {
          this.form.video.videoType = code;
          // this.form.video.path = '';
          // this.form.video.url = '';
        },

        @stack('admin.product.edit.vue.method')
      },

      @hook('admin.product.edit.vue.options')
    });

    function makeVariableIndexes() {
      // 每组值重复次数
      var repeats = app.variantValueRepetitions;
      var results = [];

      if (app.form.variables.length < 1) {
        return results;
      }

      for (let i = 0; i < repeats[0] * app.form.variables[0].values.length; i++) {
        results.push([]);
      }
      for (let xIndex = 0; xIndex < repeats.length; xIndex++) { // 0 - 3
        let repeat = 0;
        let itemIndex = 0;
        for (let yIndex = 0; yIndex < results.length; yIndex++) { // 0 - 36
          results[yIndex].push(itemIndex);
          repeat++;
          if (repeat >= repeats[xIndex]) {
            repeat = 0;
            itemIndex++;
            if (itemIndex >= app.form.variables[xIndex].values.length) {
              itemIndex = 0;
            }
          }
        }
      }

      return results;
    }

    $(function ($) {
      $('#brand-autocomplete').autocomplete({
        'source': function (request, response) {
          $http.get(`brands/autocomplete?name=${encodeURIComponent(request)}`, null, {hload: true}).then((res) => {
            response($.map(res.data, function (item) {
              return {label: item['name'], value: item['id']}
            }));
          })
        },
        'select': function (item) {
          $(this).val(item['label']);
          $('input[name="brand_id"]').val(item['value']);
        }
      });

      // variant-weight
      $('select[name="weight_class"]').on('change', function () {
        $('.variant-weight').text(`(${$('option:selected', this).text()})`);
      });

      $('select[name="weight_class"]').trigger('change');

      // skus[*][sku] 只能填写 数字、字母、中横线、下划线
      $(document).on('input', 'input[name^="skus"][name$="[sku]"]', function () {
        $(this).val($(this).val().replace(/[^a-zA-Z0-9-_]/g, ''));
        $(this)[0].dispatchEvent(new Event('input'));
      });
    });

    // 回车键 功能修改为 tab建的功能
    $(document).on('keydown', '*', function (e) {
      if (e.keyCode == 13) {
        e.preventDefault();
        var inputs = $(this).parents("form").eq(0).find(":input:visible:not([disabled]):not([readonly])");
        var idx = inputs.index(this);
        if (idx == inputs.length - 1) {
          inputs[0].select()
        } else {
          inputs[idx + 1].focus();
          inputs[idx + 1].select();
        }
        return false;
      }
    });
    @hook('admin.product.edit.script.after')
  </script>

  <script>
    /* 人民币 / 美元实时换算 */
    (function () {
      var box = document.getElementById('pf-convert');
      if (!box) return;

      var endpoint  = box.dataset.endpoint;
      var base      = '{{ $system_currency }}';
      var currencies = {};

      function role(name) {
        return box.querySelector('[data-role="' + name + '"]');
      }

      function currencyName(code) {
        if (code === 'CNY') return '人民币 CNY';
        if (code === 'USD') return '美元 USD';
        if (code === base) return '{{ __('admin/product.price_system') }} (' + code + ')';
        return code;
      }

      function formatMoney(code, value) {
        var item = currencies[code] || {};
        var decimals = typeof item.decimal_place === 'number' ? item.decimal_place : 2;
        var text = Number(value).toLocaleString('zh-CN', {
          minimumFractionDigits: decimals,
          maximumFractionDigits: decimals
        });
        return (item.symbol_left || '') + text + (item.symbol_right ? ' ' + item.symbol_right : '');
      }

      /* 按当前汇率换算：金额 * 目标汇率 / 来源汇率 */
      function convert(amount, fromCode, toCode) {
        var src = currencies[fromCode];
        var dst = currencies[toCode];
        if (!src || !dst || !src.rate || !dst.rate) return null;
        return amount * dst.rate / src.rate;
      }

      function render() {
        var amountNode = role('amount');
        var fromNode   = role('from');
        var results    = role('results');
        if (!amountNode || !fromNode || !results) return;

        results.innerHTML = '';

        var amount = parseFloat(amountNode.value);
        var from   = (fromNode.value || base).toUpperCase();
        if (!isFinite(amount) || amount < 0) {
          var hint = document.createElement('span');
          hint.className = 'pf-convert__result is-empty';
          hint.textContent = '{{ __('admin/product.price_convert_empty') }}';
          results.appendChild(hint);
          return;
        }

        var targets = [base.toUpperCase(), 'CNY', 'USD'].filter(function (code, index, list) {
          return code && code !== from && list.indexOf(code) === index;
        });

        targets.forEach(function (code) {
          if (!currencies[code]) return;
          var value = convert(amount, from, code);
          if (value === null) return;

          var chip = document.createElement('span');
          chip.className = 'pf-convert__result';
          var label = document.createElement('em');
          label.textContent = currencyName(code);
          var val = document.createElement('b');
          val.textContent = formatMoney(code, value);
          chip.appendChild(label);
          chip.appendChild(val);
          results.appendChild(chip);
        });

        if (!results.children.length) {
          var empty = document.createElement('span');
          empty.className = 'pf-convert__result is-empty';
          empty.textContent = '—';
          results.appendChild(empty);
        }
      }

      function applyRates(data) {
        currencies = {};
        (data.currencies || []).forEach(function (item) {
          currencies[item.code] = item;
        });

        var cny = currencies['CNY'];
        var rateText = role('rate');
        if (cny) {
          rateText.textContent = '1 ' + base + ' = ' + cny.rate.toFixed(4) + ' CNY';
        } else {
          rateText.textContent = '{{ __('admin/product.price_convert_failed') }}';
        }

        var meta = role('meta');
        meta.textContent = '{{ __('admin/product.price_convert_updated') }} ' + (data.updated_at || '') +
          (data.source ? ' · ' + data.source : '');

        render();
      }

      function loadRates(refresh) {
        var url = endpoint + (refresh ? '?refresh=1' : '');
        fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        })
          .then(function (res) { return res.json(); })
          .then(function (json) {
            if (json && json.status === 'success' && json.data) {
              applyRates(json.data);
            } else {
              role('rate').textContent = '{{ __('admin/product.price_convert_failed') }}';
            }
          })
          .catch(function () {
            role('rate').textContent = '{{ __('admin/product.price_convert_failed') }}';
          });
      }

      /* 价格输入框右侧币种可切换：系统货币 <-> 人民币，自动互换算 */
      var currencyFields = [];
      document.querySelectorAll('.pf-price-row .input-group').forEach(function (group) {
        var input = group.querySelector('input[name^="skus[0]"]');
        var badge = group.querySelector('.input-group-text');
        if (!input || !badge) return;

        input.dataset.pfCurrency = base;
        badge.classList.add('pf-cur-toggle');
        badge.title = '{{ __('admin/product.currency_switch_hint') }}';
        badge.innerHTML = '<span data-role="cur">' + base + '</span><i class="bi bi-chevron-expand pf-cur-toggle__icon"></i>';

        badge.addEventListener('click', function () {
          var current = input.dataset.pfCurrency || base;
          var target  = current === 'CNY' ? base : 'CNY';
          if (!currencies[target] || !currencies[current]) {
            return;
          }
          if (input.value !== '') {
            var converted = Number(input.value) * currencies[target].rate / currencies[current].rate;
            input.value = converted.toFixed(2);
          }
          input.dataset.pfCurrency = target;
          badge.querySelector('[data-role="cur"]').textContent = target;
          input.dispatchEvent(new Event('input', { bubbles: true }));
        });

        currencyFields.push({ input: input, badge: badge });
      });

      /* 提交前统一换算回系统货币，保证入库币种不变 */
      function normalizeCurrencies() {
        currencyFields.forEach(function (field) {
          var current = field.input.dataset.pfCurrency;
          if (current && current !== base && currencies[current] && currencies[base] && field.input.value !== '') {
            var converted = Number(field.input.value) * currencies[base].rate / currencies[current].rate;
            field.input.value = converted.toFixed(2);
            field.input.dataset.pfCurrency = base;
            field.badge.querySelector('[data-role="cur"]').textContent = base;
          }
        });
      }
      document.addEventListener('submit', normalizeCurrencies, true);

      var amountNode = role('amount');
      var fromNode   = role('from');
      if (amountNode) {
        amountNode.addEventListener('input', render);
      }
      if (fromNode) {
        fromNode.addEventListener('change', render);
      }
      render();

      var refreshBtn = role('refresh');
      if (refreshBtn) {
        refreshBtn.addEventListener('click', function () { loadRates(true); });
      }

      loadRates(false);
    })();
  </script>
@endpush
