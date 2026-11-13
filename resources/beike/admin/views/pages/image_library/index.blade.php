@extends('admin::layouts.master')

@section('title', __('admin/image_library.title'))

@section('page-title-back', true)

@section('content')
  @php
    $formatSize = function ($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    };

    $queryBase = array_filter([
        'keyword'    => $keyword !== '' ? $keyword : null,
        'type'       => $type !== '' ? $type : null,
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date'   => $endDate !== '' ? $endDate : null,
        'sort'       => $sort,
        'order'      => $order,
        'per_page'   => $perPage,
    ], function ($value) {
        return $value !== null;
    });

    $breadcrumbs    = $folder === '' ? [] : explode('/', $folder);
    $breadcrumbPath = '';
  @endphp

  @if (session('success'))
    <x-admin-alert type="success" msg="{{ session('success') }}" class="mt-4" />
  @endif
  @if ($errors->has('error'))
    <x-admin-alert type="danger" msg="{{ $errors->first('error') }}" class="mt-4" />
  @endif

  <div class="mb-3">
    <h5 class="mb-1">{{ __('admin/image_library.title') }}</h5>
    <div class="text-secondary font-size-12">{{ __('admin/image_library.description') }}</div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-3 col-xl-2">
      <div class="card folder-panel">
        <div class="card-body p-2">
          <div class="folder-panel-head">
            <span class="folder-panel-title">{{ __('admin/image_library.folder') }}</span>
          </div>
          @can('file_manager_create')
            <button type="button" class="folder-create-btn" id="create-folder-btn">
              <i class="bi bi-folder-plus"></i>{{ __('admin/image_library.folder_create') }}
            </button>
          @endcan
          <a class="folder-item {{ $folder === '' ? 'active' : '' }}"
             href="{{ admin_route('image_library.index', $queryBase) }}">
            <i class="bi bi-collection"></i>
            <span class="folder-name">{{ __('admin/image_library.all_images') }}</span>
            <span class="folder-count">{{ $allTotal }}</span>
          </a>
          @foreach ($folders as $item)
            @php
              $parentPath = str_contains($item['path'], '/') ? substr($item['path'], 0, strrpos($item['path'], '/')) : '';
            @endphp
            <a class="folder-item folder-node {{ $folder === $item['path'] ? 'active' : '' }}"
               href="{{ admin_route('image_library.index', array_merge($queryBase, ['folder' => $item['path']])) }}"
               style="padding-left: {{ 0.35 + $item['depth'] * 0.9 }}rem"
               data-folder="{{ $item['path'] }}"
               data-name="{{ $item['name'] }}"
               data-depth="{{ $item['depth'] }}"
               data-parent="{{ $parentPath }}"
               data-autorename="{{ request('new_folder') === $item['path'] ? '1' : '0' }}"
               title="{{ $item['path'] }}">
              @if ($item['has_children'] ?? false)
                <span class="folder-toggle" role="button" tabindex="0" aria-label="{{ __('admin/image_library.toggle_folder') }}">
                  <i class="bi bi-chevron-right"></i>
                </span>
              @else
                <span class="folder-toggle is-empty"></span>
              @endif
              <i class="bi {{ $folder === $item['path'] ? 'bi-folder2-open' : 'bi-folder' }}"></i>
              <span class="folder-name">{{ $item['name'] }}</span>
              <span class="folder-count">{{ $item['count'] }}</span>
              @canany(['file_manager_update', 'file_manager_delete'])
                <span class="folder-actions">
                  @can('file_manager_update')
                    <span class="folder-action" role="button" tabindex="0" data-action="rename"
                          title="{{ __('admin/image_library.folder_rename') }}">
                      <i class="bi bi-pencil"></i>
                    </span>
                  @endcan
                  @can('file_manager_delete')
                    <span class="folder-action is-danger" role="button" tabindex="0" data-action="delete"
                          title="{{ __('admin/image_library.folder_delete') }}">
                      <i class="bi bi-trash"></i>
                    </span>
                  @endcan
                </span>
              @endcanany
            </a>
          @endforeach
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-9 col-xl-10">
      <div class="card">
        <div class="card-body">
          <form method="GET" action="{{ admin_route('image_library.index') }}" class="filter-grid">
            <input type="hidden" name="folder" value="{{ $folder }}">
            <div class="filter-item is-keyword">
              <label>{{ __('admin/image_library.search') }}</label>
              <input type="text" name="keyword" class="form-control" value="{{ $keyword }}"
                     placeholder="{{ __('admin/image_library.search_placeholder') }}">
            </div>
            <div class="filter-item">
              <label>{{ __('admin/image_library.type') }}</label>
              <select name="type" class="form-select">
                <option value="">{{ __('admin/image_library.all_types') }}</option>
                <option value="jpg" {{ $type === 'jpg' ? 'selected' : '' }}>{{ __('admin/image_library.type_jpg') }}</option>
                <option value="png" {{ $type === 'png' ? 'selected' : '' }}>{{ __('admin/image_library.type_png') }}</option>
                <option value="gif" {{ $type === 'gif' ? 'selected' : '' }}>{{ __('admin/image_library.type_gif') }}</option>
                <option value="webp" {{ $type === 'webp' ? 'selected' : '' }}>{{ __('admin/image_library.type_webp') }}</option>
              </select>
            </div>
            <div class="filter-item">
              <label>{{ __('admin/image_library.date_from') }}</label>
              <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="filter-item">
              <label>{{ __('admin/image_library.date_to') }}</label>
              <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="filter-item">
              <label>{{ __('admin/image_library.sort') }}</label>
              <select name="sort" class="form-select">
                <option value="created" {{ $sort === 'created' ? 'selected' : '' }}>{{ __('admin/image_library.sort_created') }}</option>
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>{{ __('admin/image_library.sort_name') }}</option>
                <option value="size" {{ $sort === 'size' ? 'selected' : '' }}>{{ __('admin/image_library.sort_size') }}</option>
              </select>
            </div>
            <div class="filter-item">
              <label>{{ __('admin/image_library.order') }}</label>
              <select name="order" class="form-select">
                <option value="desc" {{ $order === 'desc' ? 'selected' : '' }}>{{ __('admin/image_library.order_desc') }}</option>
                <option value="asc" {{ $order === 'asc' ? 'selected' : '' }}>{{ __('admin/image_library.order_asc') }}</option>
              </select>
            </div>
            <div class="filter-item">
              <label>{{ __('admin/image_library.per_page') }}</label>
              <select name="per_page" class="form-select">
                @foreach ([20, 40, 60, 80, 100] as $size)
                  <option value="{{ $size }}" {{ $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
              </select>
            </div>
            <div class="filter-actions">
              <button type="submit" class="btn btn-primary flex-fill">{{ __('admin/image_library.search') }}</button>
              <a href="{{ admin_route('image_library.index') }}" class="btn btn-outline-secondary">{{ __('admin/image_library.reset') }}</a>
            </div>
          </form>

          <div class="library-toolbar">
            <div class="toolbar-group">
              <div class="form-check mb-0">
                <input type="checkbox" class="form-check-input" id="select-all">
                <label class="form-check-label" for="select-all">{{ __('admin/image_library.select_all') }}</label>
              </div>
              <button type="button" class="btn btn-link btn-sm p-0 d-none" id="selection-clear">
                {{ __('admin/image_library.unselect_all') }}
              </button>
              <span class="text-secondary font-size-12" id="selected-count">{{ __('admin/image_library.selected_count', ['count' => 0]) }}</span>
              <button type="button" class="btn btn-outline-primary btn-sm" id="batch-download" disabled>
                <i class="bi bi-download"></i> {{ __('admin/image_library.download_selected') }}
              </button>
              @can('file_manager_delete')
                <button type="button" class="btn btn-outline-danger btn-sm" id="batch-delete" disabled>
                  <i class="bi bi-trash"></i> {{ __('admin/image_library.delete_selected') }}
                </button>
              @endcan
            </div>
            <div class="toolbar-group">
              <div class="btn-group btn-group-sm mode-toggle" role="group" aria-label="{{ __('admin/image_library.view_mode') }}">
                <button type="button" class="btn btn-outline-secondary" data-mode="grid" title="{{ __('admin/image_library.view_grid') }}">
                  <i class="bi bi-grid-3x3-gap"></i><span class="d-none d-xl-inline ms-1">{{ __('admin/image_library.view_grid') }}</span>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-mode="waterfall" title="{{ __('admin/image_library.view_waterfall') }}">
                  <i class="bi bi-columns"></i><span class="d-none d-xl-inline ms-1">{{ __('admin/image_library.view_waterfall') }}</span>
                </button>
              </div>
              <i class="bi bi-info-circle text-secondary font-size-12" data-bs-toggle="tooltip"
                 title="{{ __('admin/image_library.drag_sort_hint') }}"></i>
              @can('file_manager_create')
                <button type="button" class="btn btn-primary btn-sm" id="upload-btn">
                  <i class="bi bi-cloud-arrow-up"></i> {{ __('admin/image_library.upload') }}
                </button>
                <input type="file" id="upload-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>
              @endcan
              <span class="text-secondary font-size-12">
                {{ __('admin/image_library.total_images', ['count' => $total]) }}
                · {{ __('admin/image_library.total_size', ['size' => $formatSize($totalSize)]) }}
              </span>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center gap-1 mb-3 font-size-12">
            <a href="{{ admin_route('image_library.index', $queryBase) }}">{{ __('admin/image_library.all_images') }}</a>
            @foreach ($breadcrumbs as $segment)
              @php $breadcrumbPath = $breadcrumbPath === '' ? $segment : $breadcrumbPath . '/' . $segment; @endphp
              <span class="text-secondary">/</span>
              @if ($loop->last)
                <span class="text-secondary">{{ $segment }}</span>
              @else
                <a href="{{ admin_route('image_library.index', array_merge($queryBase, ['folder' => $breadcrumbPath])) }}">{{ $segment }}</a>
              @endif
            @endforeach
          </div>

          @if ($images->isEmpty())
            <div class="text-center py-5 text-secondary">
              <i class="bi bi-images fs-1 d-block mb-2"></i>
              <div>{{ __('admin/image_library.no_images') }}</div>
              <div class="font-size-12 mt-1">{{ __('admin/image_library.no_images_hint') }}</div>
            </div>
          @else
            <div class="image-grid is-grid" id="image-grid">
              @foreach ($images as $image)
                <div class="image-card" draggable="true"
                     data-path="{{ $image['path'] }}"
                     data-name="{{ $image['name'] }}"
                     data-url="{{ $image['url'] }}"
                     data-thumb="{{ $image['thumb'] }}"
                     data-origin="{{ $image['url'] }}"
                     data-type="{{ $image['ext'] }}">
                  <div class="image-thumb">
                    <div class="image-skeleton" aria-hidden="true"></div>
                    <img class="image-el" alt="{{ $image['name'] }}" loading="lazy"
                         data-thumb="{{ $image['thumb'] }}" data-origin="{{ $image['url'] }}">
                    <div class="image-error">
                      <i class="bi bi-image"></i>
                      <span>{{ __('admin/image_library.load_fail') }}</span>
                    </div>
                  </div>
                  <input type="checkbox" class="image-checkbox" value="{{ $image['path'] }}"
                         aria-label="{{ $image['name'] }}">
                  <div class="image-actions">
                    <button type="button" class="image-action-btn image-preview" title="{{ __('admin/image_library.preview') }}">
                      <i class="bi bi-eye"></i>
                    </button>
                    <button type="button" class="image-action-btn image-download" title="{{ __('admin/image_library.download') }}">
                      <i class="bi bi-download"></i>
                    </button>
                    <button type="button" class="image-action-btn image-copy" data-url="{{ $image['url'] }}"
                            title="{{ __('admin/image_library.copy_link') }}">
                      <i class="bi bi-link-45deg"></i>
                    </button>
                    @can('file_manager_delete')
                      <button type="button" class="image-action-btn image-delete" title="{{ __('admin/image_library.delete') }}">
                        <i class="bi bi-trash"></i>
                      </button>
                    @endcan
                  </div>
                  <div class="image-meta">
                    <div class="image-name" title="{{ $image['name'] }}">{{ $image['name'] }}</div>
                    <div class="image-sub">
                      {{ $image['width'] ? $image['width'] . '×' . $image['height'] : '-' }}
                      · {{ $formatSize($image['size']) }}
                    </div>
                    <div class="image-sub" title="{{ $image['relative_folder'] }}">
                      {{ $image['relative_folder'] ?: __('admin/image_library.root_folder') }}
                    </div>
                  </div>
                </div>
              @endforeach
            </div>

            <div class="d-flex justify-content-end mt-4">
              {{ $images->withQueryString()->links('admin::vendor/pagination/bootstrap-4') }}
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection

@push('footer')
  <div class="image-lightbox" id="image-lightbox" aria-hidden="true">
    <div class="lightbox-toolbar">
      <span class="lightbox-counter"></span>
      <button type="button" class="lightbox-btn" data-action="zoom-out" title="{{ __('admin/image_library.zoom_out') }}"><i class="bi bi-zoom-out"></i></button>
      <button type="button" class="lightbox-btn" data-action="zoom-in" title="{{ __('admin/image_library.zoom_in') }}"><i class="bi bi-zoom-in"></i></button>
      <button type="button" class="lightbox-btn" data-action="rotate-left" title="{{ __('admin/image_library.rotate_left') }}"><i class="bi bi-arrow-counterclockwise"></i></button>
      <button type="button" class="lightbox-btn" data-action="rotate-right" title="{{ __('admin/image_library.rotate_right') }}"><i class="bi bi-arrow-clockwise"></i></button>
      <button type="button" class="lightbox-btn" data-action="reset" title="{{ __('admin/image_library.reset_view') }}"><i class="bi bi-arrow-repeat"></i></button>
      <button type="button" class="lightbox-btn" data-action="download" title="{{ __('admin/image_library.download') }}"><i class="bi bi-download"></i></button>
      <button type="button" class="lightbox-btn" data-action="close" title="{{ __('admin/image_library.close') }}"><i class="bi bi-x-lg"></i></button>
    </div>
    <button type="button" class="lightbox-nav prev" data-action="prev" title="{{ __('admin/image_library.lightbox_prev') }}"><i class="bi bi-chevron-left"></i></button>
    <div class="lightbox-stage">
      <img class="lightbox-image" alt="" draggable="false">
    </div>
    <button type="button" class="lightbox-nav next" data-action="next" title="{{ __('admin/image_library.lightbox_next') }}"><i class="bi bi-chevron-right"></i></button>
  </div>

  <div class="image-context-menu" id="image-context-menu">
    <button type="button" class="context-menu-item" data-action="preview"><i class="bi bi-eye"></i>{{ __('admin/image_library.preview') }}</button>
    <button type="button" class="context-menu-item" data-action="download"><i class="bi bi-download"></i>{{ __('admin/image_library.download') }}</button>
    <button type="button" class="context-menu-item" data-action="copy"><i class="bi bi-link-45deg"></i>{{ __('admin/image_library.copy_link') }}</button>
    @can('file_manager_delete')
      <button type="button" class="context-menu-item text-danger" data-action="delete"><i class="bi bi-trash"></i>{{ __('admin/image_library.delete') }}</button>
    @endcan
  </div>

  <form method="POST" action="{{ admin_route('image_library.download') }}" id="image-download-form" class="d-none">
    @csrf
    <div id="image-download-inputs"></div>
  </form>

  <style>
    .folder-panel { position: sticky; top: 1rem; }
    .folder-panel-title {
      padding: .25rem .5rem;
      font-size: .72rem;
      font-weight: 600;
      letter-spacing: .05em;
      text-transform: uppercase;
      color: var(--bs-secondary-color);
    }
    .folder-item {
      display: flex;
      align-items: center;
      gap: .4rem;
      padding: .4rem .5rem;
      border-radius: .5rem;
      color: inherit;
      font-size: .85rem;
      text-decoration: none;
      transition: background-color .15s ease, color .15s ease;
    }
    .folder-item:hover { color: inherit; background: var(--bs-tertiary-bg, #f8f9fa); }
    .folder-item.active { color: var(--bs-primary); font-weight: 600; background: rgba(var(--bs-primary-rgb), .1); }
    .folder-node.is-hidden { display: none; }
    .folder-toggle {
      display: inline-flex;
      flex: 0 0 auto;
      align-items: center;
      justify-content: center;
      width: 1rem;
      height: 1rem;
      color: var(--bs-secondary-color);
      cursor: pointer;
      border-radius: .25rem;
      transition: background-color .15s ease, color .15s ease;
    }
    .folder-toggle:hover { color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .12); }
    .folder-toggle i { font-size: .7rem; transform: rotate(90deg); transition: transform .15s ease; }
    .folder-node.is-collapsed .folder-toggle i { transform: rotate(0deg); }
    .folder-toggle.is-empty { visibility: hidden; cursor: default; }
    .folder-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .folder-count { font-size: .72rem; color: var(--bs-secondary-color); }

    .folder-panel-head { display: flex; align-items: center; justify-content: space-between; }
    .folder-create-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .35rem;
      width: 100%;
      margin-bottom: .4rem;
      padding: .4rem .5rem;
      font-size: .8rem;
      font-weight: 500;
      color: var(--bs-primary);
      background: rgba(var(--bs-primary-rgb), .06);
      border: 1px dashed rgba(var(--bs-primary-rgb), .45);
      border-radius: .5rem;
      transition: background-color .15s ease, border-color .15s ease;
    }
    .folder-create-btn:hover { background: rgba(var(--bs-primary-rgb), .12); border-color: var(--bs-primary); }
    .folder-create-btn:disabled { opacity: .6; cursor: not-allowed; }
    .folder-actions { display: none; align-items: center; gap: .1rem; }
    .folder-item:hover .folder-actions,
    .folder-item:focus-within .folder-actions { display: inline-flex; }
    @media (hover: none) { .folder-actions { display: inline-flex; } }
    .folder-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1.35rem;
      height: 1.35rem;
      color: var(--bs-secondary-color);
      border-radius: .3rem;
      cursor: pointer;
      transition: background-color .15s ease, color .15s ease;
    }
    .folder-action:hover { color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), .12); }
    .folder-action.is-danger:hover { color: var(--bs-danger); background: rgba(var(--bs-danger-rgb), .1); }
    .folder-action i { font-size: .72rem; }
    .folder-rename-input {
      flex: 1;
      min-width: 0;
      height: 1.6rem;
      padding: 0 .35rem;
      font-size: .82rem;
      color: var(--bs-body-color);
      background: var(--bs-body-bg);
      border: 1px solid var(--bs-primary);
      border-radius: .35rem;
      outline: none;
    }
    .folder-rename-input.is-invalid {
      border-color: var(--bs-danger);
      box-shadow: 0 0 0 .15rem rgba(var(--bs-danger-rgb), .15);
    }

    .filter-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: .75rem;
      align-items: end;
      margin-bottom: 1rem;
    }
    .filter-item label {
      display: block;
      margin-bottom: .25rem;
      font-size: .75rem;
      color: var(--bs-secondary-color);
    }
    .filter-item.is-keyword { grid-column: span 2; }
    .filter-actions { display: flex; gap: .5rem; grid-column: span 2; }
    @media (max-width: 991.98px) {
      .filter-item.is-keyword { grid-column: span 2; }
      .filter-actions { grid-column: 1 / -1; }
    }
    @media (max-width: 575.98px) {
      .filter-item.is-keyword { grid-column: 1 / -1; }
    }

    .library-toolbar {
      display: flex;
      flex-wrap: wrap;
      gap: .75rem;
      align-items: center;
      justify-content: space-between;
      margin-bottom: .75rem;
    }
    .toolbar-group { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .mode-toggle .btn.active { color: #fff; background: var(--bs-primary); border-color: var(--bs-primary); }

    .image-grid { transition: opacity .25s ease; }
    .image-grid.is-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 1rem;
    }
    .image-grid.is-waterfall { column-width: 200px; column-gap: 1rem; }

    .image-card {
      position: relative;
      background: var(--bs-body-bg);
      border: 1px solid var(--bs-border-color);
      border-radius: .75rem;
      overflow: hidden;
      cursor: pointer;
      animation: imageCardIn .25s ease both;
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .image-card:hover {
      transform: translateY(-2px);
      border-color: var(--bs-primary);
      box-shadow: 0 8px 20px rgba(0, 0, 0, .1);
    }
    .image-card.selected { border-color: var(--bs-primary); box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), .35); }
    .image-card.is-dragging { opacity: .45; transform: scale(.98); }
    .image-grid.is-waterfall .image-card {
      display: inline-block;
      width: 100%;
      margin: 0 0 1rem;
      break-inside: avoid;
      animation: none;
    }
    @keyframes imageCardIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: none; }
    }

    .image-thumb {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background: var(--bs-tertiary-bg, #f5f5f5);
    }
    .image-grid.is-grid .image-thumb { aspect-ratio: 1 / 1; padding: .5rem; }
    .image-grid.is-waterfall .image-thumb { min-height: 140px; }
    .image-el {
      opacity: 0;
      transition: opacity .3s ease;
    }
    .image-grid.is-grid .image-el { max-width: 100%; max-height: 100%; object-fit: contain; }
    .image-grid.is-waterfall .image-el { display: block; width: 100%; height: auto; }
    .image-card.is-loaded .image-el { opacity: 1; }
    .image-skeleton {
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, rgba(0, 0, 0, .04) 25%, rgba(0, 0, 0, .09) 37%, rgba(0, 0, 0, .04) 63%);
      background-size: 400% 100%;
      animation: imageShimmer 1.2s ease infinite;
    }
    @keyframes imageShimmer {
      0% { background-position: 100% 0; }
      100% { background-position: 0 0; }
    }
    .image-card.is-loaded .image-skeleton,
    .image-card.has-error .image-skeleton { display: none; }
    .image-error {
      display: none;
      flex-direction: column;
      align-items: center;
      gap: .25rem;
      padding: 1rem;
      font-size: .72rem;
      color: var(--bs-secondary-color);
    }
    .image-error i { font-size: 1.5rem; }
    .image-card.has-error .image-error { display: flex; }
    .image-card.has-error .image-el { display: none; }

    .image-checkbox {
      position: absolute;
      top: .5rem;
      left: .5rem;
      width: 1.05rem;
      height: 1.05rem;
      cursor: pointer;
      z-index: 2;
    }
    .image-actions {
      position: absolute;
      top: .5rem;
      right: .5rem;
      display: flex;
      gap: .25rem;
      z-index: 2;
      opacity: 0;
      transform: translateY(-2px);
      transition: opacity .15s ease, transform .15s ease;
    }
    .image-card:hover .image-actions { opacity: 1; transform: none; }
    @media (hover: none) { .image-actions { opacity: 1; transform: none; } }
    .image-action-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1.75rem;
      height: 1.75rem;
      padding: 0;
      color: #fff;
      background: rgba(0, 0, 0, .6);
      border: 0;
      border-radius: 50%;
      transition: background-color .15s ease, transform .15s ease;
    }
    .image-action-btn:hover { background: rgba(0, 0, 0, .82); transform: scale(1.06); }
    .image-meta { padding: .5rem .625rem .625rem; line-height: 1.3; }
    .image-name { font-size: .8rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .image-sub { font-size: .72rem; color: var(--bs-secondary-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .image-lightbox {
      position: fixed;
      inset: 0;
      z-index: 10000;
      display: none;
      background: rgba(10, 12, 16, .93);
      opacity: 0;
      transition: opacity .2s ease;
    }
    .image-lightbox.is-open { display: block; opacity: 1; }
    body.lightbox-open { overflow: hidden; }
    .lightbox-toolbar {
      position: absolute;
      top: 0;
      right: 0;
      left: 0;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: .5rem;
      padding: .75rem 1rem;
      color: #fff;
      background: linear-gradient(to bottom, rgba(0, 0, 0, .5), transparent);
    }
    .lightbox-counter { margin-right: auto; font-size: .85rem; opacity: .8; }
    .lightbox-btn,
    .lightbox-nav {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      background: rgba(255, 255, 255, .12);
      border: 0;
      border-radius: .5rem;
      transition: background-color .15s ease;
    }
    .lightbox-btn { width: 2.25rem; height: 2.25rem; }
    .lightbox-btn:hover, .lightbox-nav:hover { background: rgba(255, 255, 255, .28); }
    .lightbox-stage {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 4rem 5rem;
      overflow: hidden;
      touch-action: pan-y;
    }
    .lightbox-image {
      max-width: 100%;
      max-height: 100%;
      transition: transform .18s ease;
      transform-origin: center center;
      user-select: none;
      -webkit-user-drag: none;
    }
    .lightbox-image.is-zoomed { cursor: grab; }
    .lightbox-image.is-dragging { cursor: grabbing; transition: none; }
    .lightbox-nav {
      position: absolute;
      top: 50%;
      z-index: 2;
      width: 2.75rem;
      height: 2.75rem;
      font-size: 1.25rem;
      transform: translateY(-50%);
    }
    .lightbox-nav.prev { left: 1rem; }
    .lightbox-nav.next { right: 1rem; }
    @media (max-width: 767.98px) {
      .lightbox-stage { padding: 3.5rem .5rem; }
      .lightbox-toolbar { gap: .35rem; padding: .5rem; }
      .lightbox-btn { width: 2rem; height: 2rem; }
      .lightbox-nav { width: 2.25rem; height: 2.25rem; }
      .lightbox-nav.prev { left: .25rem; }
      .lightbox-nav.next { right: .25rem; }
    }

    .image-context-menu {
      position: fixed;
      z-index: 10001;
      display: none;
      min-width: 170px;
      padding: .35rem;
      background: var(--bs-body-bg);
      border: 1px solid var(--bs-border-color);
      border-radius: .6rem;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .16);
    }
    .image-context-menu.is-open { display: block; animation: contextMenuIn .12s ease both; }
    @keyframes contextMenuIn {
      from { opacity: 0; transform: translateY(-4px); }
      to { opacity: 1; transform: none; }
    }
    .context-menu-item {
      display: flex;
      align-items: center;
      gap: .5rem;
      width: 100%;
      padding: .45rem .6rem;
      font-size: .85rem;
      color: inherit;
      text-align: left;
      background: none;
      border: 0;
      border-radius: .45rem;
    }
    .context-menu-item:hover { background: var(--bs-tertiary-bg, #f5f5f5); }
    .context-menu-item.text-danger:hover { background: rgba(var(--bs-danger-rgb), .08); }
  </style>

  <script src="{{ asset('vendor/image-library/image-library-utils.js') }}"></script>
  <script>
    (function () {
      const utils = window.ImageLibraryUtils;
      const grid = document.getElementById('image-grid');
      const urls = {!! json_encode([
          'destroy' => admin_route('image_library.destroy'),
          'upload'  => admin_route('image_library.upload'),
          'folders' => [
              'create'  => admin_route('image_library.folders.create'),
              'rename'  => admin_route('image_library.folders.rename'),
              'destroy' => admin_route('image_library.folders.destroy'),
          ],
      ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
      const texts = {!! json_encode([
          'selectImages'  => __('admin/image_library.select_images'),
          'deleteConfirm' => __('admin/image_library.delete_confirm'),
          'deleteFail'    => __('admin/image_library.delete_fail'),
          'downloadFail'  => __('admin/image_library.download_fail'),
          'copySuccess'   => __('admin/image_library.copy_success'),
          'copyFail'      => __('admin/image_library.copy_fail'),
          'selectedCount' => __('admin/image_library.selected_count', ['count' => '__COUNT__']),
          'hint'          => __('common.text_hint'),
          'folderNameEmpty'    => __('admin/image_library.folder_name_empty'),
          'folderNameTooLong'  => __('admin/image_library.folder_name_too_long'),
          'folderNameInvalid'  => __('admin/image_library.folder_name_invalid'),
          'folderNameExists'   => __('admin/image_library.folder_name_exists'),
          'folderCreateFail'   => __('admin/image_library.folder_create_fail'),
          'folderRenameFail'   => __('admin/image_library.folder_rename_fail'),
          'folderDeleteFail'   => __('admin/image_library.folder_delete_fail'),
          'folderDeleteConfirm' => __('admin/image_library.folder_delete_confirm'),
      ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
      const currentFolder = {!! json_encode($folder) !!};
      const storageKeys = {
          mode: 'image_library_mode',
          order: 'image_library_order',
          collapsedFolders: 'image_library_collapsed_folders',
      };

      // 文件夹树折叠 / 展开
      (function initFolderTree() {
        const panel = document.querySelector('.folder-panel');
        if (!panel) {
          return;
        }

        let collapsed = [];
        try {
          collapsed = JSON.parse(localStorage.getItem(storageKeys.collapsedFolders) || '[]');
        } catch (e) {
          collapsed = [];
        }
        if (!Array.isArray(collapsed)) {
          collapsed = [];
        }

        const expandAncestors = function (path) {
          if (!path) {
            return;
          }
          const parts = path.split('/');
          const ancestors = [];
          let current = '';
          parts.forEach(function (segment, index) {
            current = current === '' ? segment : current + '/' + segment;
            if (index < parts.length - 1) {
              ancestors.push(current);
            }
          });
          collapsed = collapsed.filter(function (item) {
            // 展开当前所在目录及其所有父级
            return ancestors.indexOf(item) === -1 && item !== path;
          });
        };

        const apply = function () {
          panel.querySelectorAll('.folder-node').forEach(function (node) {
            let parent = node.dataset.parent || '';
            let hidden = false;
            while (parent) {
              if (collapsed.indexOf(parent) > -1) {
                hidden = true;
                break;
              }
              parent = parent.indexOf('/') > -1 ? parent.substring(0, parent.lastIndexOf('/')) : '';
            }
            node.classList.toggle('is-hidden', hidden);
            node.classList.toggle('is-collapsed', collapsed.indexOf(node.dataset.folder) > -1);
          });
        };

        const toggle = function (node) {
          const path = node.dataset.folder;
          const index = collapsed.indexOf(path);
          if (index > -1) {
            collapsed.splice(index, 1);
          } else {
            collapsed.push(path);
          }
          localStorage.setItem(storageKeys.collapsedFolders, JSON.stringify(collapsed));
          apply();
        };

        panel.addEventListener('click', function (e) {
          const button = e.target.closest('.folder-toggle');
          if (!button || button.classList.contains('is-empty')) {
            return;
          }
          e.preventDefault();
          e.stopPropagation();
          const node = button.closest('.folder-node');
          if (node) {
            toggle(node);
          }
        });

        panel.addEventListener('keydown', function (e) {
          if (e.key !== 'Enter' && e.key !== ' ') {
            return;
          }
          const button = e.target.closest('.folder-toggle');
          if (!button || button.classList.contains('is-empty')) {
            return;
          }
          e.preventDefault();
          const node = button.closest('.folder-node');
          if (node) {
            toggle(node);
          }
        });

        expandAncestors(currentFolder || '');
        apply();
      })();

      // 文件夹管理：新建 / 重命名 / 删除
      (function initFolderManage() {
        const panel = document.querySelector('.folder-panel');
        const createBtn = document.getElementById('create-folder-btn');
        if (!panel) {
          return;
        }

        const folderNodes = function () {
          return Array.from(panel.querySelectorAll('.folder-node'));
        };

        const siblingsOf = function (node) {
          return folderNodes().filter(function (item) {
            return (item.dataset.parent || '') === (node.dataset.parent || '');
          });
        };

        const showFieldError = function (input, message) {
          input.classList.add('is-invalid');
          layer.msg(message);
          input.focus();
          input.select();
        };

        const validateName = function (name, node) {
          if (!name) {
            return texts.folderNameEmpty;
          }
          if (name.length > 50) {
            return texts.folderNameTooLong;
          }
          if (name === '.' || name === '..' || name.indexOf('..') > -1 || /[\\/:*?"<>|]/.test(name)) {
            return texts.folderNameInvalid;
          }

          const duplicated = siblingsOf(node).some(function (item) {
            return item !== node && (item.dataset.name || '').toLowerCase() === name.toLowerCase();
          });

          return duplicated ? texts.folderNameExists : '';
        };

        const startRename = function (node) {
          if (!node || node.querySelector('.folder-rename-input')) {
            return;
          }

          const nameEl = node.querySelector('.folder-name');
          const oldName = node.dataset.name || '';
          if (!nameEl) {
            return;
          }

          const input = document.createElement('input');
          input.type = 'text';
          input.className = 'folder-rename-input';
          input.value = oldName;
          input.maxLength = 50;
          input.setAttribute('aria-label', oldName);

          nameEl.classList.add('d-none');
          nameEl.after(input);
          input.focus();
          input.select();

          let done = false;
          let submitting = false;

          const cancel = function () {
            if (done || submitting) {
              return;
            }
            done = true;
            input.remove();
            nameEl.classList.remove('d-none');
          };

          const commit = function () {
            if (done) {
              return;
            }

            const name = input.value.trim();
            const error = validateName(name, node);
            if (error) {
              showFieldError(input, error);
              return;
            }
            if (name === oldName) {
              cancel();
              return;
            }

            done = true;
            submitting = true;
            input.disabled = true;
            const load = layer.load(2);

            $.ajax({
              url: urls.folders.rename,
              type: 'POST',
              data: {path: node.dataset.folder, name: name},
              success: function (res) {
                layer.close(load);
                if (res.status === 'success') {
                  layer.msg(res.message);
                  setTimeout(function () {
                    const oldPath = node.dataset.folder;
                    const newPath = res.data && res.data.path;

                    // 当前所在目录被重命名时同步更新 folder 参数
                    if (newPath && (currentFolder === oldPath || currentFolder.indexOf(oldPath + '/') === 0)) {
                      const url = new URL(window.location.href);
                      url.searchParams.set('folder', newPath + currentFolder.slice(oldPath.length));
                      window.location.href = url.toString();
                      return;
                    }

                    window.location.reload();
                  }, 600);
                  return;
                }

                done = false;
                submitting = false;
                input.disabled = false;
                showFieldError(input, res.message || texts.folderRenameFail);
              },
              error: function (xhr) {
                layer.close(load);
                done = false;
                submitting = false;
                input.disabled = false;
                showFieldError(input, (xhr.responseJSON && xhr.responseJSON.message) || texts.folderRenameFail);
              }
            });
          };

          input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
              e.preventDefault();
              e.stopPropagation();
              commit();
            } else if (e.key === 'Escape') {
              e.preventDefault();
              e.stopPropagation();
              cancel();
            } else {
              input.classList.remove('is-invalid');
            }
          });

          input.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
          });

          input.addEventListener('blur', function () {
            cancel();
          });
        };

        const deleteFolder = function (node) {
          const name = node.dataset.name || '';
          const message = texts.folderDeleteConfirm.replace(':name', name);

          layer.confirm(message, {icon: 3, title: texts.hint}, function (index) {
            layer.close(index);
            const load = layer.load(2);

            $.ajax({
              url: urls.folders.destroy,
              type: 'POST',
              data: {_method: 'DELETE', path: node.dataset.folder},
              success: function (res) {
                layer.close(load);
                layer.msg(res.message);
                if (res.status !== 'success') {
                  return;
                }

                setTimeout(function () {
                  const deleted = node.dataset.folder;
                  const url = new URL(window.location.href);
                  url.searchParams.delete('new_folder');

                  if (currentFolder === deleted || currentFolder.indexOf(deleted + '/') === 0) {
                    const parent = node.dataset.parent || '';
                    if (parent) {
                      url.searchParams.set('folder', parent);
                    } else {
                      url.searchParams.delete('folder');
                    }
                  }

                  window.location.href = url.toString();
                }, 600);
              },
              error: function (xhr) {
                layer.close(load);
                layer.msg((xhr.responseJSON && xhr.responseJSON.message) || texts.folderDeleteFail);
              }
            });
          });
        };

        if (createBtn) {
          createBtn.addEventListener('click', function () {
            const btn = this;
            btn.disabled = true;
            const load = layer.load(2);

            $.ajax({
              url: urls.folders.create,
              type: 'POST',
              data: {parent: currentFolder},
              success: function (res) {
                layer.close(load);
                btn.disabled = false;
                if (res.status !== 'success') {
                  layer.msg(res.message || texts.folderCreateFail);
                  return;
                }

                layer.msg(res.message);
                const url = new URL(window.location.href);
                url.searchParams.set('new_folder', res.data.path);
                setTimeout(function () {
                  window.location.href = url.toString();
                }, 400);
              },
              error: function (xhr) {
                layer.close(load);
                btn.disabled = false;
                layer.msg((xhr.responseJSON && xhr.responseJSON.message) || texts.folderCreateFail);
              }
            });
          });
        }

        panel.addEventListener('click', function (e) {
          const action = e.target.closest('.folder-action');
          if (!action) {
            return;
          }
          e.preventDefault();
          e.stopPropagation();

          const node = action.closest('.folder-node');
          if (!node) {
            return;
          }

          if (action.dataset.action === 'rename') {
            startRename(node);
          } else if (action.dataset.action === 'delete') {
            deleteFolder(node);
          }
        });

        panel.addEventListener('keydown', function (e) {
          if (e.key !== 'Enter' && e.key !== ' ') {
            return;
          }
          const action = e.target.closest('.folder-action');
          if (!action) {
            return;
          }
          e.preventDefault();
          e.stopPropagation();

          const node = action.closest('.folder-node');
          if (!node) {
            return;
          }

          if (action.dataset.action === 'rename') {
            startRename(node);
          } else if (action.dataset.action === 'delete') {
            deleteFolder(node);
          }
        });

        // 新建完成后自动进入重命名状态
        const autoRename = panel.querySelector('.folder-node[data-autorename="1"]');
        if (autoRename) {
          autoRename.scrollIntoView({block: 'nearest'});
          autoRename.dataset.autorename = '0';
          try {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('new_folder');
            window.history.replaceState({}, '', cleanUrl.toString());
          } catch (e) {
          }
          setTimeout(function () {
            startRename(autoRename);
          }, 200);
        }
      })();

      if (!grid) {
        initUpload();
        return;
      }

      let mode = localStorage.getItem(storageKeys.mode) === 'waterfall' ? 'waterfall' : 'grid';
      let selected = [];
      let dragCard = null;

      const selectAll = document.getElementById('select-all');
      const selectedCount = document.getElementById('selected-count');
      const selectionClear = document.getElementById('selection-clear');
      const batchDelete = document.getElementById('batch-delete');
      const batchDownload = document.getElementById('batch-download');
      const cards = () => Array.from(grid.querySelectorAll('.image-card'));

      function sourceFor(img) {
        return mode === 'waterfall' ? img.dataset.origin : img.dataset.thumb;
      }

      // 懒加载 + 加载状态 + 失败占位
      function initLazyLoad() {
        const images = Array.from(grid.querySelectorAll('.image-el'));
        const load = function (img) {
          if (img.dataset.loaded) {
            return;
          }
          img.dataset.loaded = '1';

          img.addEventListener('load', function () {
            img.closest('.image-card').classList.add('is-loaded');
          });
          img.addEventListener('error', function () {
            img.closest('.image-card').classList.add('has-error');
          });
          img.src = sourceFor(img);
        };

        if (!('IntersectionObserver' in window)) {
          images.forEach(load);
          return;
        }

        const observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              load(entry.target);
              observer.unobserve(entry.target);
            }
          });
        }, {rootMargin: '300px 0px'});

        images.forEach(function (img) {
          observer.observe(img);
        });
      }

      // 展示模式切换
      function applyMode(next, silent) {
        mode = next === 'waterfall' ? 'waterfall' : 'grid';
        grid.classList.toggle('is-grid', mode === 'grid');
        grid.classList.toggle('is-waterfall', mode === 'waterfall');

        document.querySelectorAll('.mode-toggle [data-mode]').forEach(function (btn) {
          btn.classList.toggle('active', btn.dataset.mode === mode);
        });

        cards().forEach(function (card) {
          card.setAttribute('draggable', mode === 'grid' ? 'true' : 'false');
        });

        grid.querySelectorAll('.image-el').forEach(function (img) {
          if (img.dataset.loaded) {
            const src = sourceFor(img);
            if (img.getAttribute('src') !== src) {
              img.src = src;
            }
          }
        });

        if (!silent) {
          localStorage.setItem(storageKeys.mode, mode);
        }

        if (mode === 'grid') {
          applySavedOrder();
        }
      }

      // 选择与批量操作
      function isSelected(path) {
        return selected.indexOf(path) > -1;
      }

      function setSelected(path, checked) {
        if (isSelected(path) !== !!checked) {
          selected = utils.toggleSelection(selected, path);
        }
      }

      function updateSelectionUI() {
        if (selectedCount) {
          selectedCount.textContent = texts.selectedCount.replace('__COUNT__', selected.length);
        }
        if (selectionClear) {
          selectionClear.classList.toggle('d-none', selected.length === 0);
        }
        if (batchDelete) {
          batchDelete.disabled = selected.length === 0;
        }
        if (batchDownload) {
          batchDownload.disabled = selected.length === 0;
        }

        const all = cards();
        if (selectAll) {
          selectAll.checked = all.length > 0 && selected.length === all.length;
          selectAll.indeterminate = selected.length > 0 && selected.length < all.length;
        }

        all.forEach(function (card) {
          card.classList.toggle('selected', isSelected(card.dataset.path));
          const checkbox = card.querySelector('.image-checkbox');
          if (checkbox) {
            checkbox.checked = isSelected(card.dataset.path);
          }
        });
      }

      function selectedPaths() {
        return selected.slice();
      }

      function deleteImages(paths) {
        if (!paths.length) {
          layer.msg(texts.selectImages);
          return;
        }

        layer.confirm(texts.deleteConfirm, {icon: 3, title: texts.hint}, function (index) {
          layer.close(index);
          const load = layer.load(2);
          $.ajax({
            url: urls.destroy,
            type: 'POST',
            data: {_method: 'DELETE', paths: paths},
            success: function (res) {
              layer.close(load);
              layer.msg(res.message);
              if (res.status === 'success') {
                setTimeout(function () {
                  window.location.reload();
                }, 600);
              }
            },
            error: function (xhr) {
              layer.close(load);
              layer.msg(xhr.responseJSON?.message || texts.deleteFail);
            }
          });
        });
      }

      function downloadOne(url) {
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        link.rel = 'noopener';
        document.body.appendChild(link);
        link.click();
        link.remove();
      }

      function downloadZip(paths) {
        if (!paths.length) {
          layer.msg(texts.selectImages);
          return;
        }

        const form = document.getElementById('image-download-form');
        const inputs = document.getElementById('image-download-inputs');
        inputs.innerHTML = '';
        paths.forEach(function (path) {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'paths[]';
          input.value = path;
          inputs.appendChild(input);
        });
        form.submit();
      }

      function copyText(text) {
        const fallback = function () {
          const input = document.createElement('textarea');
          input.value = text;
          document.body.appendChild(input);
          input.select();
          const ok = document.execCommand('copy');
          document.body.removeChild(input);
          layer.msg(ok ? texts.copySuccess : texts.copyFail);
        };

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(text).then(function () {
            layer.msg(texts.copySuccess);
          }).catch(fallback);
        } else {
          fallback();
        }
      }

      // 预览灯箱
      const lightbox = document.getElementById('image-lightbox');
      const lightboxStage = lightbox.querySelector('.lightbox-stage');
      const lightboxImage = lightbox.querySelector('.lightbox-image');
      const lightboxCounter = lightbox.querySelector('.lightbox-counter');
      const lightboxState = {list: [], index: 0, scale: 1, rotation: 0, panX: 0, panY: 0};
      let panStart = null;
      let touchStart = null;
      let lastSwipeTime = 0;

      function applyLightboxTransform() {
        const rotated = lightboxState.rotation % 180 !== 0;
        const rect = lightboxStage.getBoundingClientRect();
        const stageStyle = window.getComputedStyle(lightboxStage);
        const paddingX = parseFloat(stageStyle.paddingLeft) + parseFloat(stageStyle.paddingRight);
        const paddingY = parseFloat(stageStyle.paddingTop) + parseFloat(stageStyle.paddingBottom);
        const contentWidth = Math.max(rect.width - paddingX, 1);
        const contentHeight = Math.max(rect.height - paddingY, 1);

        // 旋转 90/270 度时交换宽高约束，避免图片被裁切
        if (rotated) {
          lightboxImage.style.maxWidth = contentHeight + 'px';
          lightboxImage.style.maxHeight = contentWidth + 'px';
        } else {
          lightboxImage.style.maxWidth = '';
          lightboxImage.style.maxHeight = '';
        }

        lightboxImage.style.transform = 'translate(' + lightboxState.panX + 'px, ' + lightboxState.panY + 'px) ' +
          'scale(' + lightboxState.scale + ') rotate(' + lightboxState.rotation + 'deg)';
        lightboxImage.classList.toggle('is-zoomed', lightboxState.scale > 1);
      }

      function resetLightboxView() {
        lightboxState.scale = 1;
        lightboxState.rotation = 0;
        lightboxState.panX = 0;
        lightboxState.panY = 0;
        applyLightboxTransform();
      }

      function renderLightbox() {
        const card = lightboxState.list[lightboxState.index];
        if (!card) {
          closeLightbox();
          return;
        }

        lightboxImage.src = card.dataset.origin;
        lightboxImage.alt = card.dataset.name || '';
        lightboxCounter.textContent = (lightboxState.index + 1) + ' / ' + lightboxState.list.length;
        resetLightboxView();

        const single = lightboxState.list.length <= 1;
        lightbox.querySelectorAll('.lightbox-nav').forEach(function (btn) {
          btn.style.visibility = single ? 'hidden' : 'visible';
        });
      }

      function openLightbox(card) {
        lightboxState.list = cards();
        lightboxState.index = lightboxState.list.indexOf(card);
        if (lightboxState.index < 0) {
          return;
        }
        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lightbox-open');
        renderLightbox();
      }

      function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('lightbox-open');
        lightboxImage.classList.remove('is-zoomed', 'is-dragging');
        panStart = null;
        touchStart = null;
        lightboxImage.removeAttribute('src');
      }

      function stepLightbox(delta) {
        const total = lightboxState.list.length;
        if (total <= 1) {
          return;
        }
        lightboxState.index = (lightboxState.index + delta + total) % total;
        renderLightbox();
      }

      function zoomLightbox(delta) {
        lightboxState.scale = utils.clampScale(lightboxState.scale + delta, 0.5, 4);
        if (lightboxState.scale <= 1) {
          lightboxState.panX = 0;
          lightboxState.panY = 0;
        }
        applyLightboxTransform();
      }

      function rotateLightbox(delta) {
        lightboxState.rotation = utils.normalizeRotation(lightboxState.rotation + delta);
        lightboxState.panX = 0;
        lightboxState.panY = 0;
        applyLightboxTransform();
      }

      // 放大后可拖动查看
      lightboxImage.addEventListener('mousedown', function (e) {
        if (lightboxState.scale <= 1) {
          return;
        }
        e.preventDefault();
        panStart = {
          x: e.clientX,
          y: e.clientY,
          panX: lightboxState.panX,
          panY: lightboxState.panY
        };
        lightboxImage.classList.add('is-dragging');
      });

      document.addEventListener('mousemove', function (e) {
        if (!panStart) {
          return;
        }
        lightboxState.panX = panStart.panX + (e.clientX - panStart.x);
        lightboxState.panY = panStart.panY + (e.clientY - panStart.y);
        applyLightboxTransform();
      });

      document.addEventListener('mouseup', function () {
        if (!panStart) {
          return;
        }
        panStart = null;
        lightboxImage.classList.remove('is-dragging');
        // 拖动结束后抑制紧随其后的 click，避免误关闭灯箱
        lastSwipeTime = Date.now();
      });

      lightboxImage.addEventListener('dblclick', function () {
        lightboxState.scale = lightboxState.scale > 1 ? 1 : 2;
        lightboxState.panX = 0;
        lightboxState.panY = 0;
        applyLightboxTransform();
      });

      // 移动端左右滑动切换、放大后拖动
      lightboxStage.addEventListener('touchstart', function (e) {
        if (e.touches.length !== 1) {
          return;
        }
        const touch = e.touches[0];
        touchStart = {
          x: touch.clientX,
          y: touch.clientY,
          time: Date.now(),
          panX: lightboxState.panX,
          panY: lightboxState.panY
        };
      }, {passive: true});

      lightboxStage.addEventListener('touchmove', function (e) {
        if (!touchStart || e.touches.length !== 1) {
          return;
        }
        const touch = e.touches[0];
        const dx = touch.clientX - touchStart.x;
        const dy = touch.clientY - touchStart.y;

        if (lightboxState.scale > 1) {
          e.preventDefault();
          lightboxState.panX = touchStart.panX + dx;
          lightboxState.panY = touchStart.panY + dy;
          applyLightboxTransform();
        }
      }, {passive: false});

      lightboxStage.addEventListener('touchend', function (e) {
        if (!touchStart) {
          return;
        }
        const touch = e.changedTouches[0];
        const dx = touch.clientX - touchStart.x;
        const dy = touch.clientY - touchStart.y;
        const duration = Date.now() - touchStart.time;

        if (lightboxState.scale <= 1 && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) && duration < 700) {
          lastSwipeTime = Date.now();
          stepLightbox(dx < 0 ? 1 : -1);
        } else if (Math.abs(dx) > 10 || Math.abs(dy) > 10) {
          lastSwipeTime = Date.now();
        }
        touchStart = null;
      }, {passive: true});

      window.addEventListener('resize', function () {
        if (lightbox.classList.contains('is-open')) {
          applyLightboxTransform();
        }
      });

      // 右键菜单
      const contextMenu = document.getElementById('image-context-menu');
      let contextCard = null;

      function openContextMenu(card, x, y) {
        contextCard = card;
        contextMenu.classList.add('is-open');
        const rect = contextMenu.getBoundingClientRect();
        const left = Math.min(x, window.innerWidth - rect.width - 8);
        const top = Math.min(y, window.innerHeight - rect.height - 8);
        contextMenu.style.left = Math.max(8, left) + 'px';
        contextMenu.style.top = Math.max(8, top) + 'px';
      }

      function closeContextMenu() {
        contextMenu.classList.remove('is-open');
        contextCard = null;
      }

      // 拖拽排序（固定网格模式）
      function saveOrder() {
        localStorage.setItem(storageKeys.order, JSON.stringify(cards().map(function (card) {
          return card.dataset.path;
        })));
      }

      function applySavedOrder() {
        let saved = [];
        try {
          saved = JSON.parse(localStorage.getItem(storageKeys.order) || '[]');
        } catch (e) {
          saved = [];
        }
        if (!Array.isArray(saved) || !saved.length) {
          return;
        }

        const list = cards();
        const paths = list.map(function (card) {
          return card.dataset.path;
        });
        const ordered = utils.reorderBySavedOrder(paths, saved);
        const map = {};
        list.forEach(function (card) {
          map[card.dataset.path] = card;
        });
        ordered.forEach(function (path) {
          if (map[path]) {
            grid.appendChild(map[path]);
          }
        });
      }

      // 上传
      function initUpload() {
        const uploadBtn = document.getElementById('upload-btn');
        const uploadInput = document.getElementById('upload-input');
        if (!uploadBtn || !uploadInput) {
          return;
        }

        uploadBtn.addEventListener('click', function () {
          uploadInput.click();
        });

        uploadInput.addEventListener('change', function () {
          const files = Array.prototype.slice.call(this.files || []);
          if (!files.length) {
            return;
          }

          const load = layer.load(2);
          let failed = false;

          const uploadNext = function () {
            if (!files.length) {
              layer.close(load);
              if (!failed) {
                window.location.reload();
              }
              return;
            }

            const formData = new FormData();
            formData.append('file', files.shift());
            formData.append('folder', currentFolder);

            $.ajax({
              url: urls.upload,
              type: 'POST',
              data: formData,
              processData: false,
              contentType: false,
              success: uploadNext,
              error: function (xhr) {
                failed = true;
                layer.close(load);
                layer.msg(xhr.responseJSON?.message
                  || xhr.responseJSON?.errors?.file?.[0]
                  || {!! json_encode(__('admin/image_library.upload_fail')) !!});
              }
            });
          };

          uploadNext();
        });
      }

      // 事件绑定
      initLazyLoad();
      applyMode(mode, true);
      initUpload();
      updateSelectionUI();

      document.querySelectorAll('.mode-toggle [data-mode]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          applyMode(this.dataset.mode);
        });
      });

      grid.addEventListener('change', function (e) {
        const checkbox = e.target.closest('.image-checkbox');
        if (!checkbox) {
          return;
        }
        setSelected(checkbox.value, checkbox.checked);
        updateSelectionUI();
      });

      grid.addEventListener('click', function (e) {
        if (e.button !== 0 || e.target.closest('.image-action-btn, .image-checkbox')) {
          return;
        }
        const card = e.target.closest('.image-card');
        if (!card) {
          return;
        }
        setSelected(card.dataset.path, !isSelected(card.dataset.path));
        updateSelectionUI();
      });

      grid.addEventListener('dblclick', function (e) {
        if (e.target.closest('.image-action-btn, .image-checkbox')) {
          return;
        }
        const card = e.target.closest('.image-card');
        if (card) {
          openLightbox(card);
        }
      });

      grid.addEventListener('click', function (e) {
        const card = e.target.closest('.image-card');
        if (!card) {
          return;
        }

        if (e.target.closest('.image-preview')) {
          openLightbox(card);
        } else if (e.target.closest('.image-download')) {
          downloadOne(card.dataset.origin);
        } else if (e.target.closest('.image-copy')) {
          copyText(card.dataset.url);
        } else if (e.target.closest('.image-delete')) {
          deleteImages([card.dataset.path]);
        }
      });

      grid.addEventListener('contextmenu', function (e) {
        const card = e.target.closest('.image-card');
        if (!card) {
          return;
        }
        e.preventDefault();
        openContextMenu(card, e.clientX, e.clientY);
      });

      contextMenu.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-action]');
        if (!btn || !contextCard) {
          return;
        }

        const action = btn.dataset.action;
        const card = contextCard;
        closeContextMenu();

        if (action === 'preview') {
          openLightbox(card);
        } else if (action === 'download') {
          downloadOne(card.dataset.origin);
        } else if (action === 'copy') {
          copyText(card.dataset.url);
        } else if (action === 'delete') {
          deleteImages([card.dataset.path]);
        }
      });

      document.addEventListener('click', function (e) {
        if (!e.target.closest('#image-context-menu')) {
          closeContextMenu();
        }
      });
      window.addEventListener('scroll', closeContextMenu, true);
      window.addEventListener('resize', closeContextMenu);

      if (selectAll) {
        selectAll.addEventListener('change', function () {
          cards().forEach(function (card) {
            setSelected(card.dataset.path, selectAll.checked);
          });
          updateSelectionUI();
        });
      }

      if (selectionClear) {
        selectionClear.addEventListener('click', function () {
          selected = [];
          updateSelectionUI();
        });
      }

      if (batchDelete) {
        batchDelete.addEventListener('click', function () {
          deleteImages(selectedPaths());
        });
      }

      if (batchDownload) {
        batchDownload.addEventListener('click', function () {
          downloadZip(selectedPaths());
        });
      }

      lightbox.addEventListener('click', function (e) {
        if (Date.now() - lastSwipeTime < 300) {
          return;
        }

        if (e.target === lightbox || e.target.closest('.lightbox-stage') === e.target) {
          closeLightbox();
          return;
        }

        const btn = e.target.closest('[data-action]');
        if (!btn) {
          return;
        }

        const action = btn.dataset.action;
        if (action === 'prev') {
          stepLightbox(-1);
        } else if (action === 'next') {
          stepLightbox(1);
        } else if (action === 'zoom-in') {
          zoomLightbox(0.25);
        } else if (action === 'zoom-out') {
          zoomLightbox(-0.25);
        } else if (action === 'rotate-left') {
          rotateLightbox(-90);
        } else if (action === 'rotate-right') {
          rotateLightbox(90);
        } else if (action === 'reset') {
          resetLightboxView();
        } else if (action === 'download') {
          const card = lightboxState.list[lightboxState.index];
          if (card) {
            downloadOne(card.dataset.origin);
          }
        } else if (action === 'close') {
          closeLightbox();
        }
      });

      lightboxImage.addEventListener('wheel', function (e) {
        e.preventDefault();
        zoomLightbox(e.deltaY < 0 ? 0.2 : -0.2);
      }, {passive: false});

      document.addEventListener('keydown', function (e) {
        if (contextMenu.classList.contains('is-open') && e.key === 'Escape') {
          closeContextMenu();
          return;
        }
        if (!lightbox.classList.contains('is-open')) {
          return;
        }

        if (e.key === 'Escape') {
          closeLightbox();
        } else if (e.key === 'ArrowLeft') {
          e.preventDefault();
          stepLightbox(-1);
        } else if (e.key === 'ArrowRight') {
          e.preventDefault();
          stepLightbox(1);
        } else if (e.key === '+' || e.key === '=') {
          e.preventDefault();
          zoomLightbox(0.25);
        } else if (e.key === '-') {
          e.preventDefault();
          zoomLightbox(-0.25);
        }
      });

      grid.addEventListener('dragstart', function (e) {
        if (mode !== 'grid') {
          e.preventDefault();
          return;
        }
        const card = e.target.closest('.image-card');
        if (!card) {
          return;
        }
        dragCard = card;
        card.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        try {
          e.dataTransfer.setData('text/plain', card.dataset.path);
        } catch (err) {}
      });

      grid.addEventListener('dragover', function (e) {
        if (!dragCard) {
          return;
        }
        e.preventDefault();
        const target = e.target.closest('.image-card');
        if (!target || target === dragCard) {
          return;
        }
        const rect = target.getBoundingClientRect();
        const after = (e.clientX - rect.left) > rect.width / 2;
        grid.insertBefore(dragCard, after ? target.nextSibling : target);
      });

      grid.addEventListener('dragend', function () {
        if (!dragCard) {
          return;
        }
        dragCard.classList.remove('is-dragging');
        dragCard = null;
        saveOrder();
      });
    })();
  </script>
@endpush
