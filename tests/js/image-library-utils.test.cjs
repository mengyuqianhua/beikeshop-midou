const test = require('node:test');
const assert = require('node:assert');
const utils = require('../../public/vendor/image-library/image-library-utils.js');

test('clampScale 限制缩放范围并处理非法值', () => {
  assert.strictEqual(utils.clampScale(1), 1);
  assert.strictEqual(utils.clampScale(0.1), 0.5);
  assert.strictEqual(utils.clampScale(10), 4);
  assert.strictEqual(utils.clampScale('2'), 2);
  assert.strictEqual(utils.clampScale('abc'), 1);
  assert.strictEqual(utils.clampScale(0, 1, 3), 1);
  assert.strictEqual(utils.clampScale(5, 1, 3), 3);
});

test('normalizeRotation 将角度规范到 0-359', () => {
  assert.strictEqual(utils.normalizeRotation(0), 0);
  assert.strictEqual(utils.normalizeRotation(90), 90);
  assert.strictEqual(utils.normalizeRotation(360), 0);
  assert.strictEqual(utils.normalizeRotation(450), 90);
  assert.strictEqual(utils.normalizeRotation(-90), 270);
  assert.strictEqual(utils.normalizeRotation(-450), 270);
});

test('toggleSelection 添加与移除选中项且不修改原数组', () => {
  const origin = ['a.jpg'];
  const added = utils.toggleSelection(origin, 'b.jpg');
  assert.deepStrictEqual(added, ['a.jpg', 'b.jpg']);
  assert.deepStrictEqual(origin, ['a.jpg']);

  const removed = utils.toggleSelection(added, 'a.jpg');
  assert.deepStrictEqual(removed, ['b.jpg']);

  assert.deepStrictEqual(utils.toggleSelection(null, 'c.jpg'), ['c.jpg']);
});

test('reorderBySavedOrder 按保存顺序重排，未记录项保持相对顺序', () => {
  assert.deepStrictEqual(
    utils.reorderBySavedOrder(['a.jpg', 'b.jpg', 'c.jpg'], ['c.jpg', 'a.jpg']),
    ['c.jpg', 'a.jpg', 'b.jpg']
  );

  assert.deepStrictEqual(
    utils.reorderBySavedOrder(['a.jpg', 'b.jpg'], []),
    ['a.jpg', 'b.jpg']
  );

  assert.deepStrictEqual(
    utils.reorderBySavedOrder(['a.jpg', 'b.jpg'], ['x.jpg', 'b.jpg']),
    ['b.jpg', 'a.jpg']
  );

  assert.deepStrictEqual(
    utils.reorderBySavedOrder(['a.jpg', 'b.jpg'], ['b.jpg', 'b.jpg', 'a.jpg']),
    ['b.jpg', 'a.jpg']
  );
});

test('reorderBySavedOrder 处理空列表', () => {
  assert.deepStrictEqual(utils.reorderBySavedOrder([], ['a.jpg']), []);
  assert.deepStrictEqual(utils.reorderBySavedOrder(null, ['a.jpg']), []);
});
