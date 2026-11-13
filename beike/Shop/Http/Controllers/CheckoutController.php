<?php

/**
 * CheckoutController.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     guangda <service@guangda.work>
 * @created    2022-06-28 16:47:57
 * @modified   2022-06-28 16:47:57
 */

namespace Beike\Shop\Http\Controllers;

use Beike\Repositories\OrderRepo;
use Beike\Shop\Services\CheckoutService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
    public function index()
    {
        try {
            $data = (new CheckoutService)->checkoutData();
            $data = hook_filter('checkout.index.data', $data);

            return view('checkout', $data);
        } catch (\Exception $e) {
            return redirect(shop_route('carts.index'))->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 更改结算信息
     *
     * @param Request $request
     * @return mixed
     */
    public function update(Request $request): mixed
    {
        try {
            $requestData = $request->all();

            $data = (new CheckoutService)->update($requestData);

            return hook_filter('checkout.update.data', $data);
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        }
    }

    /**
     * 确认提交订单
     *
     * @return mixed
     * @throws \Throwable
     */
    public function confirm()
    {
        $idempotencyKey = trim((string) (request()->header('Idempotency-Key') ?: request('idempotency_key')));
        $cacheKey       = $idempotencyKey === '' ? '' : 'checkout:confirm:' . sha1($this->idempotencyScope() . '|' . $idempotencyKey);
        $lock           = null;

        try {
            if ($cacheKey !== '') {
                if ($order = $this->getCachedOrder($cacheKey)) {
                    return hook_filter('checkout.confirm.data', $order);
                }

                $lock = Cache::lock($cacheKey . ':lock', 10);
                $lock->block(10);

                // 等待锁后再次检查，避免并发的重复提交生成多笔订单
                if ($order = $this->getCachedOrder($cacheKey)) {
                    return hook_filter('checkout.confirm.data', $order);
                }
            }

            $data = (new CheckoutService)->confirm();

            if ($cacheKey !== '') {
                Cache::put($cacheKey, $data->number, now()->addMinutes(10));
            }

            if (! current_customer()) {
                session()->push('guest_order_numbers', $data->number);
            }

            return hook_filter('checkout.confirm.data', $data);
        } catch (LockTimeoutException $e) {
            return json_fail(trans('shop/carts.submitting'));
        } catch (\Exception $e) {
            return json_fail($e->getMessage());
        } finally {
            if ($lock) {
                $lock->release();
            }
        }
    }

    /**
     * 获取幂等键对应的已生成订单
     */
    private function getCachedOrder(string $cacheKey)
    {
        $number = Cache::get($cacheKey);
        if (! $number) {
            return null;
        }

        $order = OrderRepo::getOrderByNumber($number, current_customer());
        if (! $order) {
            Cache::forget($cacheKey);
        }

        return $order;
    }

    /**
     * 幂等作用域：登录客户按客户隔离，游客按会话隔离
     */
    private function idempotencyScope(): string
    {
        $customer = current_customer();

        return $customer ? 'customer:' . $customer->id : 'session:' . get_session_id();
    }

    public function success()
    {
        $order_number = request('order_number');

        $customer = current_customer();
        $order    = OrderRepo::getOrderByNumber($order_number, $customer);
        if (empty($customer) && ! in_array((string) $order_number, array_map('strval', session('guest_order_numbers', [])), true)) {
            abort(404);
        }

        $data     = hook_filter('account.order.show.data', ['order' => $order, 'html_items' => []]);

        return view('checkout/success', $data);
    }
}
