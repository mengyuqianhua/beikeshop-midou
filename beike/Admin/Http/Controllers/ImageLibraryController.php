<?php

namespace Beike\Admin\Http\Controllers;

use Beike\Admin\Services\FileManagerService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ZipArchive;

class ImageLibraryController extends Controller
{
    protected string $defaultRoute = 'image_library.index';

    /**
     * 允许管理的图片目录（相对 public 目录）
     */
    private const IMAGE_ROOT = 'image/catalog';

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * 图片库列表
     */
    public function index(Request $request)
    {
        $keyword   = trim((string) $request->get('keyword'));
        $folder    = $this->normalizeFolder((string) $request->get('folder'));
        $type      = strtolower((string) $request->get('type'));
        $type      = in_array($type, self::ALLOWED_EXTENSIONS, true) ? $type : '';
        $startDate = $this->normalizeDate((string) $request->get('start_date'));
        $endDate   = $this->normalizeDate((string) $request->get('end_date'));
        $sort      = in_array($request->get('sort'), ['created', 'name', 'size'], true) ? $request->get('sort') : 'created';
        $order     = $request->get('order') === 'asc' ? 'asc' : 'desc';
        $perPage   = (int) $request->get('per_page', 40);
        $perPage   = in_array($perPage, [20, 40, 60, 80, 100], true) ? $perPage : 40;
        $page      = max(1, (int) $request->get('page', 1));

        $allImages = $this->collectImages();

        if ($keyword !== '') {
            $allImages = array_values(array_filter($allImages, function ($item) use ($keyword) {
                return mb_stripos($item['name'], $keyword) !== false
                    || mb_stripos($item['relative_folder'], $keyword) !== false;
            }));
        }

        if ($type !== '') {
            $allImages = array_values(array_filter($allImages, function ($item) use ($type) {
                if ($type === 'jpg') {
                    return in_array($item['ext'], ['jpg', 'jpeg'], true);
                }

                return $item['ext'] === $type;
            }));
        }

        $startTime = $startDate === '' ? null : strtotime($startDate . ' 00:00:00');
        $endTime   = $endDate === '' ? null : strtotime($endDate . ' 23:59:59');
        if ($startTime !== null || $endTime !== null) {
            $allImages = array_values(array_filter($allImages, function ($item) use ($startTime, $endTime) {
                if ($startTime !== null && $item['mtime'] < $startTime) {
                    return false;
                }
                if ($endTime !== null && $item['mtime'] > $endTime) {
                    return false;
                }

                return true;
            }));
        }

        // 文件夹分类（数量统计基于当前搜索条件，空文件夹同样展示）
        $hasFilter  = $keyword !== '' || $type !== '' || $startDate !== '' || $endDate !== '';
        $folderDirs = $hasFilter ? [] : $this->collectFolderPaths();
        $folders    = $this->buildFolders($allImages, $folderDirs);
        $allTotal   = count($allImages);

        if ($folder !== '') {
            $allImages = array_values(array_filter($allImages, function ($item) use ($folder) {
                return $item['relative_folder'] === $folder
                    || str_starts_with($item['relative_folder'], $folder . '/');
            }));
        }

        usort($allImages, function ($a, $b) use ($sort, $order) {
            switch ($sort) {
                case 'name':
                    $result = strcasecmp($a['name'], $b['name']);

                    break;
                case 'size':
                    $result = $a['size'] <=> $b['size'];

                    break;
                default:
                    $result = $a['mtime'] <=> $b['mtime'];
            }

            return $order === 'asc' ? $result : -$result;
        });

        $total     = count($allImages);
        $totalSize = array_sum(array_column($allImages, 'size'));
        $items     = array_slice($allImages, ($page - 1) * $perPage, $perPage);

        $items = array_map(function ($item) {
            $item['url']   = image_origin($item['path']);
            $item['thumb'] = image_resize($item['path'], 240, 240);

            $dimensions     = @getimagesize(public_path($item['path']));
            $item['width']  = $dimensions[0] ?? 0;
            $item['height'] = $dimensions[1] ?? 0;

            return $item;
        }, $items);

        $images = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path'  => admin_route('image_library.index'),
            'query' => $request->query(),
        ]);

        return view('admin::pages.image_library.index', [
            'images'    => $images,
            'folders'   => $folders,
            'folder'    => $folder,
            'allTotal'  => $allTotal,
            'total'     => $total,
            'totalSize' => $totalSize,
            'keyword'   => $keyword,
            'type'      => $type,
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'sort'      => $sort,
            'order'     => $order,
            'perPage'   => $perPage,
        ]);
    }

    /**
     * 上传图片
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $folder = $this->normalizeFolder((string) $request->get('folder', ''));
        if ($folder !== '' && ! is_dir(public_path(self::IMAGE_ROOT . '/' . $folder))) {
            return back()->withErrors(['error' => trans('admin/image_library.folder_not_exist')]);
        }

        try {
            (new FileManagerService)->uploadFile(
                $request->file('file'),
                $folder === '' ? '/' : '/' . $folder,
                $request->file('file')->getClientOriginalName()
            );

            return back()->with('success', trans('admin/image_library.upload_success'));
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 删除图片（支持批量）
     */
    public function destroy(Request $request)
    {
        $paths = $this->requestPaths($request);
        if (empty($paths)) {
            return json_fail(trans('admin/image_library.select_images'));
        }

        $deleted = 0;
        foreach ($paths as $path) {
            $fullPath = $this->resolveImagePath($path);
            if ($fullPath && @unlink($fullPath)) {
                $deleted++;
            }
        }

        if ($deleted === 0) {
            return json_fail(trans('admin/image_library.delete_fail'));
        }

        \Log::info('Image library deleted', [
            'paths'   => $paths,
            'user_id' => auth()->id(),
            'ip'      => $request->ip(),
        ]);

        return json_success(trans('admin/image_library.delete_success', ['count' => $deleted]));
    }

    /**
     * 新建文件夹（未指定名称时自动使用「新建文件夹」+序号）
     */
    public function createFolder(Request $request)
    {
        $parent = $this->normalizeFolder((string) $request->input('parent', ''));

        // 根目录（parent 为空）直接使用图片空间根路径
        $parentDir = $parent === ''
            ? realpath(public_path(self::IMAGE_ROOT))
            : $this->resolveFolderDir($parent);

        if (! $parentDir || ! is_dir($parentDir)) {
            return json_fail(trans('admin/image_library.folder_not_exist'));
        }

        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            $name = $this->uniqueFolderName($parentDir);
        } else {
            $error = $this->validateFolderName($name, $parentDir);
            if ($error !== null) {
                return json_fail($error);
            }
        }

        $target = $parentDir . '/' . $name;
        if (! @mkdir($target, 0755)) {
            return json_fail(trans('admin/image_library.folder_create_fail'));
        }

        return json_success(trans('admin/image_library.folder_create_success'), [
            'path' => $parent === '' ? $name : $parent . '/' . $name,
            'name' => $name,
        ]);
    }

    /**
     * 重命名文件夹
     */
    public function renameFolder(Request $request)
    {
        $path = $this->normalizeFolder((string) $request->input('path', ''));
        $dir  = $this->resolveFolderDir($path);
        if ($dir === null) {
            return json_fail(trans('admin/image_library.folder_not_exist'));
        }

        $name  = trim((string) $request->input('name', ''));
        $error = $this->validateFolderName($name, dirname($dir), basename($dir));
        if ($error !== null) {
            return json_fail($error);
        }

        if ($name === basename($dir)) {
            return json_success(trans('admin/image_library.folder_rename_success'), ['path' => $path]);
        }

        $target = dirname($dir) . '/' . $name;
        if (is_dir($target) || is_file($target)) {
            return json_fail(trans('admin/image_library.folder_name_exists'));
        }
        if (! @rename($dir, $target)) {
            return json_fail(trans('admin/image_library.folder_rename_fail'));
        }

        $parent  = str_contains($path, '/') ? substr($path, 0, strrpos($path, '/')) : '';
        $newPath = $parent === '' ? $name : $parent . '/' . $name;

        // 同步迁移缩略图缓存目录
        $oldCache = public_path('cache/' . self::IMAGE_ROOT . '/' . $path);
        $newCache = public_path('cache/' . self::IMAGE_ROOT . '/' . $newPath);
        if (is_dir($oldCache)) {
            if (! @rename($oldCache, $newCache)) {
                try {
                    $this->deleteDirectory($oldCache);
                } catch (\Throwable $e) {
                }
            }
        }

        return json_success(trans('admin/image_library.folder_rename_success'), ['path' => $newPath]);
    }

    /**
     * 删除文件夹（含子文件夹及全部图片）
     */
    public function destroyFolder(Request $request)
    {
        $path = $this->normalizeFolder((string) $request->input('path', ''));
        $dir  = $this->resolveFolderDir($path);
        if ($dir === null) {
            return json_fail(trans('admin/image_library.folder_not_exist'));
        }

        try {
            $this->deleteDirectory($dir);

            $cacheDir = public_path('cache/' . self::IMAGE_ROOT . '/' . $path);
            if (is_dir($cacheDir)) {
                $this->deleteDirectory($cacheDir);
            }
        } catch (\Throwable $e) {
            return json_fail(trans('admin/image_library.folder_delete_fail'));
        }

        \Log::info('Image library folder deleted', [
            'path'    => $path,
            'user_id' => auth()->id(),
            'ip'      => $request->ip(),
        ]);

        return json_success(trans('admin/image_library.folder_delete_success'));
    }

    /**
     * 打包下载图片（支持批量）
     */
    public function download(Request $request)
    {
        $paths = $this->requestPaths($request);
        if (empty($paths)) {
            return back()->withErrors(['error' => trans('admin/image_library.select_images')]);
        }

        $files = [];
        foreach ($paths as $path) {
            $fullPath = $this->resolveImagePath($path);
            if ($fullPath) {
                $files[$path] = $fullPath;
            }
        }

        if (empty($files)) {
            return back()->withErrors(['error' => trans('admin/image_library.delete_fail')]);
        }

        $zipPath = storage_path('app/image-library-' . uniqid() . '.zip');
        $zip     = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors(['error' => trans('admin/image_library.download_fail')]);
        }

        foreach ($files as $relative => $fullPath) {
            $innerName = ltrim(substr($relative, strlen(self::IMAGE_ROOT)), '/');
            if ($innerName === '') {
                $innerName = basename($fullPath);
            }

            $zip->addFile($fullPath, $innerName);
        }
        $zip->close();

        return response()->download($zipPath, 'images-' . date('YmdHis') . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * 扫描图片空间内的所有图片
     */
    private function collectImages(): array
    {
        $basePath = public_path(self::IMAGE_ROOT);
        if (! is_dir($basePath)) {
            return [];
        }

        $publicPath = rtrim(str_replace('\\', '/', public_path()), '/');
        $images     = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $fullPath       = str_replace('\\', '/', $file->getPathname());
            $relative       = ltrim(substr($fullPath, strlen($publicPath)), '/');
            $relativeFolder = trim(substr(str_replace('\\', '/', dirname($relative)), strlen(self::IMAGE_ROOT)), '/');

            $images[] = [
                'path'            => $relative,
                'name'            => $file->getFilename(),
                'folder'          => 'image/catalog' . ($relativeFolder === '' ? '' : '/' . $relativeFolder),
                'relative_folder' => $relativeFolder,
                'size'            => $file->getSize(),
                'mtime'           => $file->getMTime(),
                'ext'             => $extension,
            ];
        }

        return $images;
    }

    /**
     * 扫描图片空间内的所有目录（含空目录）
     */
    private function collectFolderPaths(): array
    {
        $basePath = public_path(self::IMAGE_ROOT);
        if (! is_dir($basePath)) {
            return [];
        }

        $basePath = realpath($basePath);
        if (! $basePath) {
            return [];
        }
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $paths    = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (! $item->isDir()) {
                continue;
            }

            $full     = str_replace('\\', '/', $item->getPathname());
            $relative = trim(substr($full, strlen($basePath)), '/');
            if ($relative !== '') {
                $paths[] = $relative;
            }
        }

        return $paths;
    }

    /**
     * 根据图片所在目录构建文件夹列表（含父级目录与图片数量）
     */
    private function buildFolders(array $images, array $folderPaths = []): array
    {
        $directCounts = [];
        foreach ($images as $image) {
            $path                = $image['relative_folder'];
            $directCounts[$path] = ($directCounts[$path] ?? 0) + 1;
        }

        $paths = $folderPaths;
        foreach (array_keys($directCounts) as $path) {
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        $folders = [];
        foreach ($paths as $path) {
            $path = trim((string) $path, '/');
            if ($path === '') {
                continue;
            }

            $current = '';
            foreach (explode('/', $path) as $index => $segment) {
                $current = $current === '' ? $segment : $current . '/' . $segment;
                if (! isset($folders[$current])) {
                    $folders[$current] = [
                        'path'  => $current,
                        'name'  => $segment,
                        'depth' => $index,
                        'count' => 0,
                    ];
                }
            }
        }

        foreach ($folders as $path => $folder) {
            $count        = 0;
            $hasChildren  = false;
            foreach ($directCounts as $imageFolder => $number) {
                if ($imageFolder === $path || str_starts_with($imageFolder, $path . '/')) {
                    $count += $number;
                }
            }
            foreach (array_keys($folders) as $otherPath) {
                if (str_starts_with($otherPath, $path . '/')) {
                    $hasChildren = true;

                    break;
                }
            }
            $folders[$path]['count']        = $count;
            $folders[$path]['has_children'] = $hasChildren;
        }

        uasort($folders, function ($a, $b) {
            return strnatcasecmp($a['path'], $b['path']);
        });

        return array_values($folders);
    }

    /**
     * 读取请求中的图片路径
     */
    private function requestPaths(Request $request): array
    {
        $paths = $request->input('paths', []);
        if (is_string($paths)) {
            $paths = [$paths];
        }
        if (! is_array($paths)) {
            return [];
        }

        return array_values(array_filter($paths, function ($path) {
            return is_string($path) && trim($path) !== '';
        }));
    }

    /**
     * 规范化文件夹参数
     */
    private function normalizeFolder(string $folder): string
    {
        $folder = trim(str_replace('\\', '/', $folder), '/');
        if ($folder === '' || str_contains($folder, '..')) {
            return '';
        }

        return $folder;
    }

    /**
     * 规范化日期参数
     */
    private function normalizeDate(string $date): string
    {
        $date = trim($date);
        if ($date === '') {
            return '';
        }

        $timestamp = strtotime($date);

        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    /**
     * 校验图片路径，仅允许操作图片空间内的图片文件
     */
    private function resolveImagePath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            return null;
        }

        if (! str_starts_with($path, self::IMAGE_ROOT . '/')) {
            return null;
        }

        $basePath = realpath(public_path(self::IMAGE_ROOT));
        $realPath = realpath(public_path($path));
        if (! $basePath || ! $realPath || ! is_file($realPath)) {
            return null;
        }

        if (! str_starts_with($realPath, str_replace('\\', '/', $basePath) . '/')) {
            return null;
        }

        if (! in_array(strtolower(pathinfo($realPath, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        return $realPath;
    }

    /**
     * 解析图片空间内的文件夹绝对路径
     */
    private function resolveFolderDir(string $path): ?string
    {
        $path = $this->normalizeFolder($path);
        if ($path === '') {
            return null;
        }

        $basePath = realpath(public_path(self::IMAGE_ROOT));
        $dirPath  = realpath(public_path(self::IMAGE_ROOT . '/' . $path));
        if (! $basePath || ! $dirPath || ! is_dir($dirPath)) {
            return null;
        }

        $basePath = str_replace('\\', '/', $basePath);
        $dirPath  = str_replace('\\', '/', $dirPath);
        if (! str_starts_with($dirPath, $basePath . '/')) {
            return null;
        }

        return $dirPath;
    }

    /**
     * 校验文件夹名称，返回错误信息或 null
     */
    private function validateFolderName(string $name, string $parentDir, string $ignore = ''): ?string
    {
        if ($name === '') {
            return trans('admin/image_library.folder_name_empty');
        }

        if (mb_strlen($name) > 50) {
            return trans('admin/image_library.folder_name_too_long');
        }

        if ($name === '.' || $name === '..' || str_contains($name, '..')
            || preg_match('#[\\\\/:*?"<>|\x00-\x1F]#u', $name)) {
            return trans('admin/image_library.folder_name_invalid');
        }

        foreach (scandir($parentDir) ?: [] as $item) {
            if ($item === '.' || $item === '..' || $item === $ignore) {
                continue;
            }
            if (is_dir($parentDir . '/' . $item) && mb_strtolower($item) === mb_strtolower($name)) {
                return trans('admin/image_library.folder_name_exists');
            }
        }

        return null;
    }

    /**
     * 生成同级目录下唯一的默认文件夹名称
     */
    private function uniqueFolderName(string $dir): string
    {
        $base  = trans('admin/image_library.folder_default_name');
        $name  = $base;
        $index = 2;

        while (is_dir($dir . '/' . $name) || is_file($dir . '/' . $name)) {
            $name = $base . $index;
            $index++;
        }

        return $name;
    }

    /**
     * 递归删除目录
     */
    private function deleteDirectory(string $dir): void
    {
        $items = scandir($dir);
        if ($items === false) {
            throw new \RuntimeException('Unable to read directory: ' . $dir);
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path) && ! is_link($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        if (! @rmdir($dir)) {
            throw new \RuntimeException('Unable to remove directory: ' . $dir);
        }
    }
}
