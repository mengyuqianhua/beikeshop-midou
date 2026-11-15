@extends('layout.master')

@section('body-class', 'page-login')

@push('header')
  <script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/element-ui/index.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('vendor/element-ui/index.css') }}">
  <style>
    #page-login .auth-shell { display: flex; justify-content: center; }
    #page-login .auth-card { width: 100%; max-width: 460px; background: #fff; border: 1px solid #eef0f3; border-radius: 20px; box-shadow: 0 24px 60px rgba(15, 23, 42, .08); padding-bottom: 26px; overflow: hidden; }
    #page-login .auth-tabs { display: flex; gap: 8px; margin: 22px 24px 10px; padding: 6px; background: #f3f4f6; border-radius: 14px; }
    #page-login .auth-tab { flex: 1; border: 0; background: transparent; padding: 11px 0; border-radius: 10px; color: #6b7280; font-weight: 600; font-size: 15px; transition: all .2s ease; }
    #page-login .auth-tab.active { background: #111827; color: #fff; box-shadow: 0 8px 18px rgba(17, 24, 39, .18); }
    #page-login .auth-body { padding: 12px 24px 0; }
    #page-login .auth-captcha, #page-login .auth-code { display: flex; align-items: center; gap: 10px; width: 100%; }
    #page-login .auth-captcha .el-input, #page-login .auth-code .el-input { flex: 1; }
    #page-login .auth-captcha-img { width: 126px; height: 44px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer; flex: 0 0 126px; }
    #page-login .auth-code-btn { height: 44px; min-width: 116px; white-space: nowrap; border-radius: 8px; }
    #page-login .auth-meta { display: flex; justify-content: flex-end; margin: -6px 0 18px; font-size: 13px; }
    #page-login .auth-submit { margin: 6px 0 12px; }
    #page-login .auth-submit .btn { height: 50px; border-radius: 12px; font-size: 16px; }
    #page-login .el-form-item { margin-bottom: 20px; }
    #page-login .el-input__inner { height: 48px; line-height: 48px; border-radius: 10px; }
    #page-login .social-wrap { padding-top: 10px; border-top: 1px dashed #e5e7eb; margin-top: 10px; }
    #page-login .social-wrap .title { text-align: center; color: #9ca3af; font-size: 13px; margin-bottom: 14px; }
    #page-login .social-buttons { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
    @media (max-width: 575.98px) {
      #page-login .auth-body { padding: 10px 18px 0; }
      #page-login .auth-tabs { margin: 16px 18px 8px; }
      #page-login .auth-captcha-img { width: 104px; flex-basis: 104px; }
      #page-login .auth-code-btn { min-width: 102px; padding-left: 10px; padding-right: 10px; }
    }
  </style>
@endpush

@section('content')
  @if (!request('iframe'))
    <x-shop-breadcrumb type="static" value="login.index" :is-full="true" />
  @endif

  <div class="{{ request('iframe') ? 'container-fluid form-iframe mt-3' : 'container py-4 py-lg-5' }}" id="page-login" v-cloak>
    @if (!request('iframe'))
      @hookwrapper('account.login.heading')
      <div class="hero-content pb-3 pb-lg-4 text-center">
        <h1 class="hero-heading">{{ __('shop/login.index') }}</h1>
        <p class="text-muted mb-0">{{ registration_allowed() ? __('shop/login.login_and_sign') : __('shop/login.login') }}</p>
      </div>
      @endhookwrapper
    @endif

    <div class="auth-shell">
      <div class="auth-card">
        <div class="auth-tabs" role="tablist">
          <button type="button" class="auth-tab" :class="{active: activeTab === 'login'}" @click="switchTab('login')">
            <i class="bi bi-box-arrow-in-right"></i> {{ __('shop/login.login') }}
          </button>
          @if (registration_allowed())
            <button type="button" class="auth-tab" :class="{active: activeTab === 'register'}" @click="switchTab('register')">
              <i class="bi bi-person-plus"></i> {{ __('shop/login.register') }}
            </button>
          @endif
        </div>

        <div class="auth-body">
          <el-form v-show="activeTab === 'login'" ref="loginForm" :model="loginForm" :rules="loginRules" :inline-message="true" v-on:submit.native.prevent>
            @hookwrapper('account.login.email')
            <el-form-item prop="email">
              <el-input v-model="loginForm.email" placeholder="{{ __('shop/login.email_address') }}" prefix-icon="el-icon-message" @keyup.enter.native="checkedBtnLogin('loginForm')"></el-input>
            </el-form-item>
            @endhookwrapper

            @hookwrapper('account.login.password')
            <el-form-item prop="password">
              <el-input v-model="loginForm.password" type="password" show-password placeholder="{{ __('shop/login.password') }}" prefix-icon="el-icon-lock" @keyup.enter.native="checkedBtnLogin('loginForm')"></el-input>
            </el-form-item>
            @endhookwrapper

            @hook('account.login.password.after')

            <el-form-item prop="captcha">
              <div class="auth-captcha">
                <el-input v-model="loginForm.captcha" maxlength="6" placeholder="{{ __('shop/login.enter_captcha') }}" prefix-icon="el-icon-picture-outline" @keyup.enter.native="checkedBtnLogin('loginForm')"></el-input>
                <img v-if="activeTab === 'login'" class="auth-captcha-img" :src="captchaUrl" alt="{{ __('shop/login.captcha') }}" title="{{ __('shop/login.captcha_refresh') }}" @click="refreshCaptcha">
              </div>
            </el-form-item>

            @hookwrapper('account.login.forget_password')
            @if (!request('iframe'))
              <div class="auth-meta">
                <a class="text-muted" href="{{ shop_route('forgotten.index') }}"><i class="bi bi-question-circle"></i> {{ __('shop/login.forget_password') }}</a>
              </div>
            @endif
            @endhookwrapper

            @hookwrapper('account.login.new.login')
            <div class="auth-submit">
              <button type="button" @click="checkedBtnLogin('loginForm')" class="btn btn-dark w-100 fw-bold">
                <i class="bi bi-box-arrow-in-right"></i> {{ __('shop/login.login') }}
              </button>
            </div>
            @endhookwrapper
          </el-form>

          @if (registration_allowed())
          <el-form v-show="activeTab === 'register'" ref="registerForm" :model="registerForm" :rules="registeRules" :inline-message="true" v-on:submit.native.prevent>
            @hookwrapper('account.login.new.email')
            <el-form-item prop="email">
              <el-input v-model="registerForm.email" placeholder="{{ __('shop/login.email_address') }}" prefix-icon="el-icon-message" @keyup.enter.native="checkedBtnLogin('registerForm')"></el-input>
            </el-form-item>
            @endhookwrapper

            @hookwrapper('account.login.new.password')
            <el-form-item prop="password">
              <el-input v-model="registerForm.password" type="password" show-password placeholder="{{ __('shop/login.password') }}" prefix-icon="el-icon-lock" @keyup.enter.native="checkedBtnLogin('registerForm')"></el-input>
            </el-form-item>
            @endhookwrapper

            <el-form-item prop="password_confirmation">
              <el-input v-model="registerForm.password_confirmation" type="password" show-password placeholder="{{ __('shop/login.please_confirm') }}" prefix-icon="el-icon-lock" @keyup.enter.native="checkedBtnLogin('registerForm')"></el-input>
            </el-form-item>

            @hook('account.login.new.confirm_password.bottom')

            <el-form-item prop="code">
              <div class="auth-code">
                <el-input v-model="registerForm.code" maxlength="6" placeholder="{{ __('shop/login.enter_email_code') }}" prefix-icon="el-icon-message"></el-input>
                <button type="button" class="btn btn-outline-dark auth-code-btn" :disabled="countdown > 0 || sendingCode" @click="sendRegisterCode">
                  @{{ sendButtonText }}
                </button>
              </div>
            </el-form-item>

            <el-form-item prop="captcha">
              <div class="auth-captcha">
                <el-input v-model="registerForm.captcha" maxlength="6" placeholder="{{ __('shop/login.enter_captcha') }}" prefix-icon="el-icon-picture-outline" @keyup.enter.native="checkedBtnLogin('registerForm')"></el-input>
                <img v-if="activeTab === 'register'" class="auth-captcha-img" :src="captchaUrl" alt="{{ __('shop/login.captcha') }}" title="{{ __('shop/login.captcha_refresh') }}" @click="refreshCaptcha">
              </div>
            </el-form-item>

            @hookwrapper('account.login.new.register')
            <div class="auth-submit">
              <button type="button" @click="checkedBtnLogin('registerForm')" class="btn btn-dark w-100 fw-bold">
                <i class="bi bi-person-plus"></i> {{ __('shop/login.register') }}
              </button>
            </div>
            @endhookwrapper
          </el-form>
          @endif

          @if($social_buttons)
            <div class="social-wrap">
              <div class="title"><span>{{ __('shop/login.third_party_logins') }}</span></div>
              <div class="social-buttons">
                @foreach($social_buttons as $button)
                  {!! $button !!}
                @endforeach
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection

@push('add-scripts')
  <script>
    let app = new Vue({
      el: '#page-login',

      data: {
        activeTab: 'login',
        captchaBaseUrl: '{{ shop_route('captcha.image') }}',
        captchaToken: Date.now().toString(36) + Math.random().toString(36).slice(2),
        sendingCode: false,
        countdown: 0,

        loginForm: {
          email: '',
          password: '',
          captcha: '',
        },

        registerForm: {
          email: '',
          password: '',
          password_confirmation: '',
          code: '',
          captcha: '',
        },

        loginRules: {
          email: [
            {required: true, message: '{{ __('shop/login.enter_email') }}', trigger: 'change'},
            {type: 'email', message: '{{ __('shop/login.email_err') }}', trigger: 'change'},
          ],
          password: [
            {required: true, message: '{{ __('shop/login.enter_password') }}', trigger: 'change'}
          ],
          captcha: [
            {required: true, message: '{{ __('shop/login.enter_captcha') }}', trigger: 'change'}
          ]
        },

        registeRules: {
          email: [
            {required: true, message: '{{ __('shop/login.enter_email') }}', trigger: 'change'},
            {type: 'email', message: '{{ __('shop/login.email_err') }}', trigger: 'change'},
          ],
          password: [
            {required: true, message: '{{ __('shop/login.enter_password') }}', trigger: 'change'}
          ],
          password_confirmation: [
            {required: true, message: '{{ __('shop/login.please_confirm') }}', trigger: 'change'},
            {validator: (rule, value, callback) => {
              if (value !== this.registerForm.password) {
                callback(new Error('{{ __('shop/login.password_err') }}'));
              } else {
                callback();
              }
            }, trigger: 'change'}
          ],
          code: [
            {required: true, message: '{{ __('shop/login.enter_email_code') }}', trigger: 'change'},
            {pattern: /^\d{6}$/, message: '{{ __('shop/login.enter_email_code') }}', trigger: 'change'}
          ],
          captcha: [
            {required: true, message: '{{ __('shop/login.enter_captcha') }}', trigger: 'change'}
          ]
        },
        @stack('login.vue.data')
      },

      computed: {
        captchaUrl() {
          return this.captchaBaseUrl + '?token=' + this.captchaToken;
        },
        sendButtonText() {
          return this.countdown > 0 ? this.countdown + 's' : '{{ __('shop/login.send_code') }}';
        }
      },

      beforeMount () {
        @hook('shop.login.vue.beforeMount')
      },

      methods: {
        switchTab(tab) {
          this.activeTab = tab;
          this.$nextTick(() => {
            const form = tab === 'login' ? 'loginForm' : 'registerForm';
            if (this.$refs[form]) {
              this.$refs[form].clearValidate();
            }
          });
        },

        refreshCaptcha() {
          this.captchaToken = Date.now().toString(36) + Math.random().toString(36).slice(2);
          this.loginForm.captcha = '';
          this.registerForm.captcha = '';
        },

        sendRegisterCode() {
          if (this.sendingCode || this.countdown > 0) {
            return;
          }

          if (!this.registerForm.email) {
            layer.msg('{{ __('shop/login.enter_email') }}', () => {});
            return;
          }

          if (!this.registerForm.captcha) {
            layer.msg('{{ __('shop/login.enter_captcha') }}', () => {});
            return;
          }

          this.sendingCode = true;

          $http.post('/register/send_code', {
            email: this.registerForm.email,
            captcha: this.registerForm.captcha,
            captcha_token: this.captchaToken
          }, {hmsg: true}).then((res) => {
            layer.msg(res.message);
            this.countdown = 60;
            this.refreshCaptcha();

            const timer = setInterval(() => {
              this.countdown--;
              if (this.countdown <= 0) {
                clearInterval(timer);
              }
            }, 1000);
          }).catch((err) => {
            layer.alert(err.response.data.message, {title: '{{ __('common.text_hint') }}', btn: ['{{ __('common.confirm') }}'], skin: 'login-alert'});
            this.refreshCaptcha();
          }).finally(() => {
            this.sendingCode = false;
          });
        },

        checkedBtnLogin(form) {
          let _data = this.loginForm, url = '/login'

          if (form == 'registerForm') {
            _data = this.registerForm, url = '/register'
          }

          this.$refs['loginForm'] && this.$refs['loginForm'].clearValidate();
          this.$refs['registerForm'] && this.$refs['registerForm'].clearValidate();

          this.$refs[form].validate((valid) => {
            if (!valid) {
              layer.msg('{{ __('shop/login.check_form') }}', () => {})
              return;
            }

            $http.post(url, Object.assign({}, _data, {captcha_token: this.captchaToken}), {hmsg: true}).then((res) => {
              layer.msg(res.message)
              @if (!request('iframe'))
                location = "{{ shop_route('account.index') }}"
              @else
                var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                setTimeout(() => {
                  parent.layer.close(index); //再执行关闭
                  parent.window.location.reload()
                }, 400);
              @endif
            }).catch((err) => {
              if (err.response.data.data && err.response.data.data.error == 'password') {
                layer.msg(err.response.data.message, ()=>{})
                this.refreshCaptcha();
                return
              }

              layer.alert(err.response.data.message, {title: '{{ __('common.text_hint') }}', btn: ['{{ __('common.confirm') }}'], skin: 'login-alert'})
              this.refreshCaptcha();
            })
          });
        },
        @stack('login.vue.method')
      },

      @hook('shop.login.vue.options')
    })

    @hook('account.login.form.js.after')

    // 监听第三方登录成功回调的页面 postMessage 消息，关闭当前窗口
    window.addEventListener('message', function (event) {
      if (event.data.type == 'social_callback' && event.data.data == 'close_window') {
        if (window.name) {
          var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
          parent.layer.close(index); //再执行关闭
          parent.window.location.reload()
        }
      }
    }, false);
  </script>
@endpush
