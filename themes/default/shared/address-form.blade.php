@hookwrapper('checkout.body.address')
<template id="address-dialog">
  <div class="address-dialog">
    <el-dialog custom-class="mobileWidth" title="{{ __('address.index') }}" :visible.sync="editShow" @close="closeAddressDialog('addressForm')" :close-on-click-modal="false">
      <el-form ref="addressForm" :rules="rules" label-position="top" :model="form" label-width="100px">
        <div class="d-flex">
          <el-form-item label="{{ __('address.name') }}" class="w-50" prop="name">
            <el-input v-model="form.name" placeholder="{{ __('address.name') }}"></el-input>
          </el-form-item>
          @if (!current_customer())
          <el-form-item label="{{ __('common.email') }}" prop="email" v-if="type == 'guest_shipping_address' || !shippingRequired" class="w-50 ms-3">
            <el-input v-model="form.email" placeholder="{{ __('common.email') }}"></el-input>
          </el-form-item>
          @else
          <el-form-item label="{{ __('address.address_1') }}"  class="w-50 ms-3" prop="address_1">
            <el-input v-model="form.address_1" placeholder="{{ __('address.address_1') }}" @blur="autoDetectAddress"></el-input>
          </el-form-item>
          @endif
        </div>
        @if (!current_customer())
        <el-form-item label="{{ __('address.address_1') }}" prop="address_1">
          <el-input v-model="form.address_1" placeholder="{{ __('address.address_1') }}" @blur="autoDetectAddress"></el-input>
        </el-form-item>
        @endif
        <div v-if="detectTip" style="margin:-10px 0 12px;font-size:12px;color:#16a34a;">
          <i class="bi bi-stars me-1"></i>@{{ detectTip }}
        </div>
        <div class="d-flex">
          <el-form-item label="{{ __('address.address_2') }}" class="w-50">
            <el-input v-model="form.address_2" placeholder="{{ __('address.address_2') }}"></el-input>
          </el-form-item>
          <el-form-item label="{{ __('address.post_code') }}" class="w-50 ms-3" @if (system_setting('base.address_post_code', '0') == '1') prop="zipcode" @endif>
            <el-input v-model="form.zipcode" placeholder="{{ __('address.post_code') }}"></el-input>
          </el-form-item>
        </div>
        <div class="d-flex dialog-address">
          <el-form-item label="{{ __('address.phone') }}" class="w-50" @if (system_setting('base.address_phoner_equired', '0') == '1') prop="phone" @endif>
            <el-input v-model="form.phone" type="text" placeholder="{{ __('address.phone') }}"></el-input>
          </el-form-item>
          <el-form-item prop="city" label="{{ __('shop/account/addresses.enter_city') }}" required class="w-50 ms-3">
            <el-input v-model="form.city" placeholder="{{ __('shop/account/addresses.enter_city') }}"></el-input>
          </el-form-item>
        </div>
        <div class="d-flex">
          <el-form-item label="{{ __('address.country') }}" required class="w-50">
            <el-select v-model="form.country_id" class="w-100" filterable placeholder="{{ __('address.country_id') }}" @change="countryChange">
              <el-option v-for="item in source.countries" :key="item.id" :label="item.name"
              :value="item.id">
              </el-option>
            </el-select>
          </el-form-item>
          <el-form-item prop="zone_id" label="{{ __('address.zone') }}" class="w-50 ms-3">
            <el-select v-model="form.zone_id" class="w-100" filterable placeholder="{{ __('address.zone') }}">
              <el-option v-for="item in source.zones" :key="item.id" :label="item.name"
                :value="item.id">
              </el-option>
            </el-select>
          </el-form-item>
        </div>
        <el-form-item label="" v-if="source.isLogin">
          <span class="me-2">{{ __('address.default') }}</span> <el-switch v-model="form.default"></el-switch>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="addressFormSubmit('addressForm')">{{ __('common.save') }}</el-button>
          <el-button @click="closeAddressDialog('addressForm')">{{ __('common.cancel') }}</el-button>
        </el-form-item>
      </el-form>
    </el-dialog>
  </div>
</template>

<script>
  Vue.component('address-dialog', {
  template: '#address-dialog',
  props: {
    value: {
      default: null
    },
  },

  data: function () {
    return {
      editShow: false,
      index: null,
      type: 'shipping_address_id',
      shippingRequired: true,
      form: {
        name: '',
        email: '',
        phone: '',
        country_id: @json((int) system_setting('base.country_id')),
        zipcode: '',
        zone_id: @json((int) system_setting('base.zone_id')),
        city: '',
        address_1: '',
        address_2: '',
        default: false,
      },

      rules: {
        name: [{
          required: true,
          message: '{{ __('shop/account/addresses.enter_name') }}',
          trigger: 'blur'
        }, ],
        email: [{
          required: true,
          type: 'email',
          message: '{{ __('shop/login.enter_email') }}',
          trigger: 'blur'
        }, ],
        address_1: [{
          required: true,
          message: ' {{ __('shop/account/addresses.enter_address') }}',
          trigger: 'blur'
        }, ],
        @if (system_setting('base.address_phoner_equired', '0') == '1')
          zipcode: [{
            required: true,
            message: ' {{ __('shop/account/addresses.enter_post_code') }}',
            trigger: 'blur'
          }, ],
        @endif
        zone_id: [{
          required: true,
          message: '{{ __('shop/account/addresses.select_province') }}',
          trigger: 'blur'
        }, ],
        city: [{
          required: true,
          message: '{{ __('shop/account/addresses.enter_city') }}',
          trigger: 'blur'
        }, ],
        @if (system_setting('base.address_phoner_equired', '0') == '1')
          phone: [{
            required: true,
            message: '{{ __('shop/account/addresses.enter_phone') }}',
            trigger: 'blur'
          }, ],
        @endif
      },

      source: {
        countries: @json($countries ?? []),
        zones: [],
        isLogin: config.isLogin,
      },

      detectTip: '',
      lastDetectedText: '',
    }
  },

  computed: {
  },

  beforeMount() {
    this.countryChange(this.form.country_id);
  },

  methods: {
    editAddress(addresses, type, shippingRequired = true) {
      this.type = type
      if (addresses) {
        this.form = addresses
      }

      this.countryChange(this.form.country_id);
      this.shippingRequired = shippingRequired;
      this.detectTip = '';
      // 编辑已有地址时不重复解析，避免改动用户已保存的内容
      this.lastDetectedText = addresses && addresses.address_1 ? String(addresses.address_1).trim() : '';
      this.editShow = true

      if (!this.form.phone) {
        $http.get('user_country', null, {hload: true}).then((res) => {
          if (res.data.callingCode) {
            this.form.phone = res.data.callingCode;
          }
        })
      }
    },

    addressFormSubmit(form) {
      const self = this;
      // 提交前再尝试一次识别，确保用户在地址输入框中直接粘贴地址后立即保存也能自动回填
      this.autoDetectAddress().then(() => {
        self.$refs[form].validate((valid) => {
          if (!valid) {
            self.$message.error('{{ __('shop/checkout.check_form') }}');
            return;
          }

          self.$emit('change', self.form)
        });
      });
    },

    closeAddressDialog() {
      this.$refs['addressForm'].resetFields();
      this.editShow = false

      Object.keys(this.form).forEach(key => this.form[key] = '')
      this.form.country_id = @json((int) system_setting('base.country_id'));
      this.form.default = false;
      this.detectTip = '';
      this.lastDetectedText = '';
    },

    countryChange(e) {
      const self = this;

      if (!e) {
        this.source.zones = [];
        return Promise.resolve();
      }

      return $http.get(`/countries/${e}/zones`, null, {
        hload: true
      }).then((res) => {
        this.source.zones = res.data.zones || [];

        if (!this.source.zones.some(e => e.id == this.form.zone_id)) {
          this.form.zone_id = '';
        }
      })
    },

    /**
     * 根据详细地址自动识别国家/省份/城市并回填，所有结果均可在表单中手动修改
     */
    autoDetectAddress() {
      const self = this;
      const text = (this.form.address_1 || '').trim();

      if (!text || text === this.lastDetectedText) {
        return Promise.resolve();
      }

      const result = this.parseAddressText(text);
      if (!result) {
        return Promise.resolve();
      }

      this.lastDetectedText = text;
      const countryId = this.findCountryId(result.countryNames);
      let task = Promise.resolve();

      if (countryId && countryId != this.form.country_id) {
        this.form.country_id = countryId;
        this.form.zone_id = '';
        task = this.countryChange(countryId);
      }

      return task.then(() => {
        self.applyParsedAddress(result);
      });
    },

    parseAddressText(text) {
      const chinese = this.parseChineseAddress(text);
      if (chinese) {
        return {type: 'cn', countryNames: ['中国', 'China'], chinese: chinese};
      }

      return this.parseInternationalAddress(text);
    },

    parseChineseAddress(text) {
      if (!/[\u4e00-\u9fa5]/.test(text)) {
        return null;
      }

      let rest = text.replace(/\s+/g, '').replace(/[，,]/g, '');
      rest = rest.replace(/^(中华人民共和国|中国)/, '');

      const keywords = this.chineseProvinceKeywords();
      let keyword = '';

      for (let i = 0; i < keywords.length; i++) {
        const index = rest.indexOf(keywords[i]);
        if (index > -1 && index <= 4) {
          keyword = keywords[i];
          rest = rest.slice(index + keyword.length);
          break;
        }
      }

      if (!keyword) {
        return null;
      }

      const suffixReg = /^(维吾尔自治区|壮族自治区|回族自治区|自治区|特别行政区|省|市)/;
      const hasSuffix = suffixReg.test(rest);
      rest = rest.replace(suffixReg, '');

      const municipalities = ['北京', '上海', '天津', '重庆'];
      const specials = ['香港', '澳门'];
      let city = '';
      let district = '';

      if (specials.indexOf(keyword) > -1) {
        city = keyword;
      } else {
        const cityMatch = rest.match(/^(.{2,12}?(?:市|自治州|地区|盟))/);
        if (cityMatch) {
          city = cityMatch[1];
          rest = rest.slice(city.length);
        }
      }

      const districtMatch = rest.match(/^(.{2,12}?(?:区|县|市|旗))/);
      if (districtMatch) {
        district = districtMatch[1];
        rest = rest.slice(district.length);
      }

      // 直辖市地址中城市通常与省级同名，仅在确认是完整行政区划时才补全城市
      if (!city && municipalities.indexOf(keyword) > -1 && (hasSuffix || district)) {
        city = keyword + '市';
      }

      // 仅识别到“XX路/XX街”这类词时，大概率只是路名而非省份，放弃识别
      if (!hasSuffix && !city && !district) {
        return null;
      }

      return {
        keyword: keyword,
        city: city,
        district: district,
        detail: rest.replace(/^[省市区县\s]+/, ''),
      };
    },

    parseInternationalAddress(text) {
      const segments = text.split(/[,，]/).map(e => e.trim()).filter(e => e);
      const result = {type: 'intl', countryNames: [], segments: segments, countryIndex: -1};

      for (let i = segments.length - 1; i >= 0; i--) {
        const names = this.countryNamesOfSegment(segments[i]);
        if (names.length) {
          result.countryNames = names;
          result.countryIndex = i;
          return result;
        }
      }

      const names = this.countryNamesInText(text);
      if (names.length) {
        result.countryNames = names;
      }

      return names.length ? result : null;
    },

    chineseProvinceKeywords() {
      return ['内蒙古', '黑龙江', '北京', '上海', '天津', '重庆', '河北', '山西', '辽宁', '吉林', '江苏', '浙江', '安徽', '福建', '江西', '山东', '河南', '湖北', '湖南', '广东', '广西', '海南', '四川', '贵州', '云南', '西藏', '陕西', '甘肃', '青海', '宁夏', '新疆', '台湾', '香港', '澳门'];
    },

    countryAliasMap() {
      return {
        'usa': 'United States', 'u.s.a': 'United States', 'u.s.': 'United States', 'us': 'United States',
        'united states': 'United States', 'united states of america': 'United States', 'america': 'United States',
        'uk': 'United Kingdom', 'u.k.': 'United Kingdom', 'united kingdom': 'United Kingdom',
        'great britain': 'United Kingdom', 'britain': 'United Kingdom', 'england': 'United Kingdom',
        'scotland': 'United Kingdom', 'wales': 'United Kingdom',
        'south korea': 'South Korea', 'korea': 'South Korea', 'republic of korea': 'South Korea',
        'russia': 'Russian Federation', 'vietnam': 'Viet Nam', 'uae': 'United Arab Emirates',
        'united arab emirates': 'United Arab Emirates',
        '美国': 'United States', '英国': 'United Kingdom', '日本': 'Japan', '澳大利亚': 'Australia', '澳洲': 'Australia',
        '加拿大': 'Canada', '德国': 'Germany', '法国': 'France', '新加坡': 'Singapore', '马来西亚': 'Malaysia',
        '泰国': 'Thailand', '越南': 'Viet Nam', '韩国': 'South Korea', '南韩': 'South Korea', '新西兰': 'New Zealand',
        '意大利': 'Italy', '西班牙': 'Spain', '荷兰': 'Netherlands', '俄罗斯': 'Russian Federation',
        '印度尼西亚': 'Indonesia', '印尼': 'Indonesia', '菲律宾': 'Philippines', '巴西': 'Brazil',
        '墨西哥': 'Mexico', '阿联酋': 'United Arab Emirates', '沙特阿拉伯': 'Saudi Arabia', '南非': 'South Africa',
        '土耳其': 'Turkey', '瑞士': 'Switzerland', '瑞典': 'Sweden', '波兰': 'Poland', '爱尔兰': 'Ireland',
        '葡萄牙': 'Portugal', '比利时': 'Belgium', '奥地利': 'Austria', '以色列': 'Israel', '埃及': 'Egypt',
        '丹麦': 'Denmark', '挪威': 'Norway', '芬兰': 'Finland', '印度': 'India'
      };
    },

    countryNamesOfSegment(segment) {
      const lower = (segment || '').trim().toLowerCase();
      if (!lower) {
        return [];
      }

      const aliases = this.countryAliasMap();
      if (aliases[lower]) {
        return [aliases[lower]];
      }

      const countries = this.source.countries || [];
      const hit = countries.find(e => (e.name || '').toLowerCase() === lower);
      return hit ? [hit.name] : [];
    },

    countryNamesInText(text) {
      const aliases = this.countryAliasMap();
      let found = '';

      Object.keys(aliases).forEach(key => {
        if (/[\u4e00-\u9fa5]/.test(key) && text.indexOf(key) > -1 && key.length > found.length) {
          found = key;
        }
      });

      return found ? [aliases[found]] : [];
    },

    findCountryId(countryNames) {
      const countries = this.source.countries || [];

      for (let i = 0; i < (countryNames || []).length; i++) {
        const target = (countryNames[i] || '').toLowerCase();
        if (!target) {
          continue;
        }

        const hit = countries.find(e => {
          const name = (e.name || '').toLowerCase();
          return name === target || name.indexOf(target) === 0 || target.indexOf(name) === 0;
        });

        if (hit) {
          return hit.id;
        }
      }

      return null;
    },

    applyParsedAddress(result) {
      if (result.type === 'cn') {
        this.applyChineseAddress(result.chinese);
      } else {
        this.applyInternationalAddress(result);
      }
    },

    applyChineseAddress(data) {
      const zones = this.source.zones || [];
      const zone = zones.find(e => (e.name || '').indexOf(data.keyword) === 0);

      if (zone) {
        this.form.zone_id = zone.id;
      }

      if (data.city) {
        this.form.city = data.city;
      }

      // 系统无区县字段，区县保留在详细地址中，避免信息丢失
      const address = (data.district || '') + (data.detail || '');
      if (address && address !== this.form.address_1) {
        this.form.address_1 = address;
      }

      this.detectTip = this.buildDetectTip([zone ? zone.name : '', data.city, data.district]);
    },

    applyInternationalAddress(result) {
      const segments = result.segments || [];
      const zones = this.source.zones || [];
      let zone = null;
      let zoneIndex = -1;

      for (let i = segments.length - 1; i >= 0; i--) {
        if (i === result.countryIndex) {
          continue;
        }

        const matched = this.matchZone(segments[i], zones);
        if (matched) {
          zone = matched;
          zoneIndex = i;
          break;
        }
      }

      if (zone) {
        this.form.zone_id = zone.zone.id;
      }

      // 国家名混写在地址中（如“日本东京都...”）时无法可靠拆分，只回填国家与省份
      if (result.countryIndex === -1) {
        const country = (this.source.countries || []).find(e => (result.countryNames || []).some(name => (e.name || '').toLowerCase() === name.toLowerCase()));
        this.detectTip = this.buildDetectTip([country ? country.name : '', zone ? zone.zone.name : '']);
        return;
      }

      const remaining = [];
      segments.forEach((segment, index) => {
        if (index === result.countryIndex || index === zoneIndex) {
          return;
        }
        if (this.cleanCity(segment)) {
          remaining.push(segment);
        }
      });

      let city = '';
      let detail = '';

      if (remaining.length > 1) {
        city = this.cleanCity(remaining[remaining.length - 1]);
        detail = remaining.slice(0, -1).join(', ');
      } else if (remaining.length === 1) {
        if (this.looksLikeStreet(remaining[0])) {
          detail = remaining[0];
        } else {
          city = this.cleanCity(remaining[0]);
        }
      }

      if (!city && zone && zone.leftover) {
        city = zone.leftover;
      }

      if (!city && zone) {
        city = zone.zone.name;
      }

      if (city) {
        this.form.city = city;
      }

      if (detail && detail !== this.form.address_1) {
        this.form.address_1 = detail;
      }

      this.detectTip = this.buildDetectTip([zone ? zone.zone.name : '', city]);
    },

    buildDetectTip(labels) {
      const list = (labels || []).filter(e => e);
      if (!list.length) {
        return '';
      }

      return '{{ __('address.auto_detected') }}：' + list.join(' / ') + '，{{ __('address.manual_adjust') }}';
    },

    matchZone(segment, zones) {
      const raw = (segment || '').trim();
      if (!raw || !zones.length) {
        return null;
      }

      const lower = raw.toLowerCase();
      const tokens = raw.split(/[\s,]+/).filter(e => e);

      for (let i = 0; i < zones.length; i++) {
        const zone = zones[i];
        const name = (zone.name || '').toLowerCase().trim();
        const code = (zone.code || '').toLowerCase().trim();

        if (name && (lower === name || lower.indexOf(name + ' ') === 0)) {
          return {zone: zone, leftover: this.zoneLeftover(raw, [zone.name])};
        }

        if (code && tokens.some(token => token.toLowerCase() === code)) {
          return {zone: zone, leftover: this.zoneLeftover(raw, [zone.name, zone.code])};
        }
      }

      return null;
    },

    zoneLeftover(segment, words) {
      let rest = segment;
      (words || []).filter(e => e).forEach(word => {
        rest = rest.replace(new RegExp(this.escapeRegExp(word), 'gi'), ' ');
      });

      return this.cleanCity(rest);
    },

    cleanCity(segment) {
      return (segment || '')
        .split(/\s+/)
        .filter(e => e && !/[0-9]/.test(e))
        .join(' ')
        .trim();
    },

    looksLikeStreet(segment) {
      return /[0-9]|\b(st|street|rd|road|ave|avenue|blvd|lane|ln|dr|drive|suite|apt|unit|floor|fl)\b/i.test(segment || '');
    },

    escapeRegExp(text) {
      return (text || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    },
  }
});
</script>
@endhookwrapper
