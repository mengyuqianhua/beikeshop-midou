# 商品中文自动翻译（AutoTranslate）

在后台所有需要填写中英双语的界面监听中文输入，自动翻译为英文并填充到对应英文输入框。覆盖商品、分类、品牌、属性/属性组、文章/文章分类、商店设置、插件设置以及设计器富文本/文本组件，同时提供手动翻译按钮与全局翻译面板。

- 插件代码：`auto_translate`
- 插件目录：`plugins/AutoTranslate`
- 版本：v1.0.0
- 适用：BeikeShop v2.0.0.31（Laravel 12 + Vue2 + Element UI + Bootstrap 5）

## 一、功能特性

- 自动翻译：中文输入停止后（默认 800ms）自动翻译并填入英文框
- 手动翻译：每个中文字段旁的「翻译」按钮、描述区「翻译此描述」、右下角「翻译全部字段」
- 字段覆盖（后台全站，脚本注入到 `admin.master.footer`）：
  - 任意 `xxx[zh_cn][字段]` → `xxx[en][字段]` 命名字段：商品/分类/品牌等多语言名称、SEO 标题/关键词/描述、简介、内容等
  - 商品描述、文章内容等 TinyMCE 富文本（标签、图片、链接、换行原样保留）
  - Element UI 语言弹窗/表单（`.language-inputs`）：属性、属性组、规格、参数、客户分组、售后原因等
  - 设计器 `text-i18n` 文本组件（按语言 Tab 配对）与 `rich-text-i18n` 富文本弹窗（按 `data-code` 配对）
  - 商店设置等 `hot_keywords[zh_cn]` 形式的多语言字段
- 格式兼容：普通文本保留换行；富文本保留 HTML 结构与特殊字符
- 开关说明：`translate_variants` 控制 Element UI / 设计器弹窗类字段，默认建议开启（=1）
- 安全保护：
  - 自动模式只填充空字段或上次自动生成的内容，不覆盖人工编辑过的英文
  - 手动按钮才强制覆盖（可在设置中改为自动覆盖）
  - 翻译失败只提示，不清空已有英文，不影响表单保存
- 服务端：后台登录鉴权 + CSRF + 60 次/分钟限流 + 单次 5 万字符上限 + 30 天结果缓存

## 二、环境要求

- PHP 8.1+，需启用 `mbstring`、`dom`、`curl`（BeikeShop 默认满足）
- 服务器可访问外网：
  - `https://translate.googleapis.com`（默认主翻译源）
  - `https://api.mymemory.translated.net`（Google 失败时的备用源）

## 三、安装

### 1. 上传插件

把 `AutoTranslate` 整个目录放到站点根目录的 `plugins/` 下：

```text
站点根目录/
└── plugins/
    └── AutoTranslate/
        ├── config.json
        ├── Bootstrap.php
        ├── columns.php
        ├── Controllers/
        ├── Lang/
        ├── Routes/
        ├── Services/
        ├── Static/
        └── Views/
```

设置属主（Linux）：

```bash
chown -R www:www plugins/AutoTranslate
```

### 2. 启用插件

1. 登录后台，进入「插件」页面。
2. 找到「商品中文自动翻译」，点击安装/启用，状态变为启用。
3. 如列表中没有出现，执行：

```bash
php artisan cache:clear
php artisan view:clear
```

> 如果部署时已通过脚本写入插件记录，可跳过安装，直接在插件页确认状态为启用。

### 3. 验证安装

打开后台任意商品的新增或编辑页面，右下角应出现「自动翻译：中文 → English」面板。

## 四、配置

进入「插件 → 商品中文自动翻译 → 设置」，可配置：

- 自动翻译
  - 默认：开启
  - 关闭后仅保留手动按钮，不再实时自动填充
- 源语言
  - 默认：中文（zh_cn）
  - 从系统已启用语言中选择
- 目标语言
  - 默认：英文（en）
  - 必须与源语言不同
- 翻译接口
  - 自动（默认）：优先 Google，失败自动降级到 MyMemory
  - Google 翻译
  - MyMemory（备用）
- 输入停止延迟（毫秒）
  - 默认 800，范围 200–5000
- 覆盖已有英文内容
  - 默认关闭
  - 关闭：只填空白字段，或更新上次由插件自动生成的内容
  - 开启：自动翻译会覆盖英文框已有内容（人工修改同样会被覆盖）
- 翻译规格/参数弹窗
  - 默认开启
  - 控制规格、属性值弹窗内的自动翻译

保存后立即生效，无需清理缓存。

## 五、使用说明

### 自动模式

在中文名称、SEO 字段、描述编辑器中输入内容，停止输入约 0.8 秒后自动翻译。字段旁会显示状态：

- 「翻译中…」：请求进行中
- 「已翻译」：成功写入英文框
- 「英文已有内容，已跳过」：英文框已有非插件生成的内容，未覆盖
- 「英文内容被手动修改过，已跳过」：英文被人工改过，自动模式不再覆盖

### 手动模式

- 点击中文字段旁的「翻译」按钮：立即翻译并覆盖该字段英文
- 描述编辑器上方「翻译此描述」：翻译当前中文描述
- 右下角面板「翻译全部字段」：依次翻译页面上所有有内容的中文字段，完成后提示成功/失败数量

### 其他说明

- 输入纯英文不会触发翻译
- 清空中文时，若英文是插件自动生成的内容，会同步清空
- 商品侧栏的「参数」在商品上只保存全局属性 ID，没有本商品的多语言输入框；本插件覆盖的是新增属性/属性值弹窗中的多语言名称
- 翻译结果缓存 30 天，重复内容不会重复请求接口

## 六、测试步骤

### 1. 自动翻译

1. 后台新建商品页面。
2. 中文名称输入：`纯棉短袖T恤`。
3. 等待约 1 秒，英文名称应变为英文翻译（示例：`Pure cotton short-sleeved T-shirt`），字段旁显示「已翻译」。

### 2. 人工内容保护

1. 手动把英文名称改为 `Manual English`。
2. 修改中文名称为其他内容。
3. 等待后英文应保持 `Manual English`，并提示已跳过。
4. 点击中文名称旁「翻译」按钮，英文被覆盖为最新翻译。

### 3. 换行与特殊字符

1. 中文 SEO 关键词输入多行内容，例如：

```text
红色
蓝色
```

2. 英文关键词应包含相同数量的换行。

### 4. 富文本

1. 在中文描述编辑器中输入带格式内容，例如加粗、列表、链接、图片。
2. 英文描述应自动生成，且段落、加粗、列表、链接、图片结构保持一致。

### 5. 规格/参数弹窗

1. 打开商品的规格编辑弹窗。
2. 在中文规格名或规格值名的输入框输入中文。
3. 对应英文输入框应自动填充。

### 6. 异常场景

1. 断网，或在设置中把翻译接口改为不可用配置。
2. 输入中文后，字段旁显示「翻译失败」，页面出现错误提示。
3. 英文框保留原内容，商品表单仍可正常提交。

### 7. 接口连通性（服务器自检）

```bash
curl -s -m 15 "https://translate.googleapis.com/translate_a/single?client=gtx&sl=zh-CN&tl=en&dt=t&q=%E4%BD%A0%E5%A5%BD"
```

返回 JSON 即表示主翻译源可用。

## 七、接口与实现

- 翻译接口：`POST /{后台路径}/auto_translate/translate`
  - 参数：`from`、`to`、`text`、`html`
  - 仅后台登录用户可访问，带 CSRF 校验，限流 60 次/分钟
- 脚本接口：`GET /{后台路径}/auto_translate/asset`
  - 返回插件前端脚本
- 前端注入点：Blade hook `admin.product.form.footer`
- 字段映射：将 `descriptions[源语言][字段]` 直接映射为 `descriptions[目标语言][字段]`
- 富文本策略：整段 HTML 交给翻译接口，校验标签序列一致；不一致时逐文本节点翻译，保证结构不变

## 八、常见问题

**1. 商品页出现 500，日志提示 Unknown named parameter**

插件 hook 回调必须能接收框架传入的命名参数。当前版本已使用可变参数处理；如自行修改 `Bootstrap.php`，请保留 `function (...$args)`。

**2. 面板没有出现**

- 确认插件状态为启用
- 确认当前页面是商品新增/编辑页
- 执行 `php artisan view:clear` 后刷新
- 浏览器控制台查看是否有脚本 404 或 JS 报错

**3. 一直提示翻译失败**

- 服务器访问 `translate.googleapis.com` 是否正常
- 把翻译接口切换为「MyMemory」验证备用源
- 查看 `storage/logs/laravel.log` 中的 `auto_translate failed`

**4. 英文没有自动更新**

- 英文框已有内容且不是插件生成的，自动模式会跳过，需手动点击「翻译」
- 或开启「覆盖已有英文内容」
- 或检查延迟设置是否过大

**5. 规格弹窗不翻译**

- 确认「翻译规格/参数弹窗」为开启
- 弹窗中的语言名称需与系统语言名称一致

## 九、卸载

1. 后台「插件」页面关闭该插件。
2. 删除目录 `plugins/AutoTranslate`。
3. 如需清理配置，删除 `settings` 表中 `type='plugin'` 且 `space='auto_translate'` 的记录；删除 `plugins` 表中 `code='auto_translate'` 的记录。

关闭插件后不会影响商品数据；已翻译的英文内容为普通商品数据，会保留。

## 十、目录结构

```text
AutoTranslate/
├── config.json                    插件元信息
├── Bootstrap.php                  注册后台页面 hook
├── columns.php                    后台设置项定义
├── Controllers/TranslateController.php
├── Routes/admin.php               翻译与脚本路由
├── Services/
│   ├── Translator.php             调度、缓存、降级
│   ├── HtmlTranslator.php         富文本结构与节点翻译
│   └── Providers/
│       ├── GoogleProvider.php
│       └── MyMemoryProvider.php
├── Lang/zh_cn/plugin.php          中文文案与设置标签
├── Lang/en/plugin.php             英文文案与设置标签
├── Static/js/auto-translate.js    前端逻辑
└── Views/script.blade.php         注入配置与脚本
```
