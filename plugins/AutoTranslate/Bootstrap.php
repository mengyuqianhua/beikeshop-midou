<?php

namespace Plugin\AutoTranslate;

class Bootstrap
{
    public function boot(): void
    {
        // 在所有后台页面底部注入脚本，覆盖商品、分类、属性、页面、设置等全部双语字段
        add_hook_blade('admin.master.footer', function (...$args) {
            try {
                return view('AutoTranslate::script')->render();
            } catch (\Throwable $e) {
                return '';
            }
        });

        // 页面设计器使用独立布局，没有 admin.master.footer 钩子，注入到其 footer-script 栈
        \Illuminate\Support\Facades\View::composer('admin::pages.design.builder.index', function ($view) {
            try {
                \Illuminate\Support\Facades\View::startPush('footer-script', view('AutoTranslate::script')->render());
            } catch (\Throwable $e) {
                // 忽略渲染异常，不影响设计器正常使用
            }
        });

        // 页脚设计器同样是独立布局，只有 header 栈可用
        \Illuminate\Support\Facades\View::composer('admin::pages.design.builder.footer', function ($view) {
            try {
                \Illuminate\Support\Facades\View::startPush('header', view('AutoTranslate::script')->render());
            } catch (\Throwable $e) {
                // 忽略渲染异常，不影响设计器正常使用
            }
        });
    }
}
