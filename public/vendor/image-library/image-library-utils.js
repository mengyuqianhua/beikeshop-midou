/**
 * 图片库前端交互工具函数
 * 通过 UMD 方式导出，页面直接引入，同时可被 Node 单元测试引用
 */
(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.ImageLibraryUtils = factory();
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  /**
   * 限制缩放比例在指定区间内
   */
  function clampScale(scale, min, max) {
    var lower = typeof min === 'number' ? min : 0.5;
    var upper = typeof max === 'number' ? max : 4;
    var value = Number(scale);

    if (!isFinite(value)) {
      return 1;
    }

    return Math.min(upper, Math.max(lower, value));
  }

  /**
   * 将旋转角度规范到 0 - 359 度
   */
  function normalizeRotation(degree) {
    var value = Number(degree) || 0;
    var normalized = value % 360;

    if (normalized < 0) {
      normalized += 360;
    }

    return normalized;
  }

  /**
   * 切换选中状态，返回新数组（不修改原数组）
   */
  function toggleSelection(selected, path) {
    var list = Array.isArray(selected) ? selected.slice() : [];
    var index = list.indexOf(path);

    if (index > -1) {
      list.splice(index, 1);
    } else {
      list.push(path);
    }

    return list;
  }

  /**
   * 按保存的顺序重排路径列表，未记录顺序的项目保持原有相对顺序并排在后面
   */
  function reorderBySavedOrder(paths, savedOrder) {
    var list = Array.isArray(paths) ? paths.slice() : [];
    if (!Array.isArray(savedOrder) || savedOrder.length === 0) {
      return list;
    }

    var rank = {};
    savedOrder.forEach(function (path, index) {
      if (rank[path] === undefined) {
        rank[path] = index;
      }
    });

    return list
      .map(function (path, index) {
        return {
          path: path,
          index: index,
          rank: rank[path] === undefined ? Infinity : rank[path],
        };
      })
      .sort(function (a, b) {
        if (a.rank === b.rank) {
          return a.index - b.index;
        }

        return a.rank - b.rank;
      })
      .map(function (item) {
        return item.path;
      });
  }

  return {
    clampScale: clampScale,
    normalizeRotation: normalizeRotation,
    toggleSelection: toggleSelection,
    reorderBySavedOrder: reorderBySavedOrder,
  };
});
