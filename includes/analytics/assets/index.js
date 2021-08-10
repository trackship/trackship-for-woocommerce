/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayLikeToArray.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) {
    arr2[i] = arr[i];
  }

  return arr2;
}

module.exports = _arrayLikeToArray;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayWithHoles.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _arrayWithHoles(arr) {
  if (Array.isArray(arr)) return arr;
}

module.exports = _arrayWithHoles;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray.js */ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js");

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return arrayLikeToArray(arr);
}

module.exports = _arrayWithoutHoles;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/assertThisInitialized.js ***!
  \**********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/classCallCheck.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/classCallCheck.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/createClass.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/createClass.js ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/getPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  module.exports["default"] = module.exports, module.exports.__esModule = true;
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/inherits.js":
/*!*********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/inherits.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var setPrototypeOf = __webpack_require__(/*! ./setPrototypeOf.js */ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js");

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/iterableToArray.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/iterableToArray.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter);
}

module.exports = _iterableToArray;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js ***!
  \*********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _iterableToArrayLimit(arr, i) {
  var _i = arr == null ? null : typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"];

  if (_i == null) return;
  var _arr = [];
  var _n = true;
  var _d = false;

  var _s, _e;

  try {
    for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) {
      _arr.push(_s.value);

      if (i && _arr.length === i) break;
    }
  } catch (err) {
    _d = true;
    _e = err;
  } finally {
    try {
      if (!_n && _i["return"] != null) _i["return"]();
    } finally {
      if (_d) throw _e;
    }
  }

  return _arr;
}

module.exports = _iterableToArrayLimit;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/nonIterableRest.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/nonIterableRest.js ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableRest;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/nonIterableSpread.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/nonIterableSpread.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

module.exports = _nonIterableSpread;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js ***!
  \**************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var _typeof = __webpack_require__(/*! @babel/runtime/helpers/typeof */ "./node_modules/@babel/runtime/helpers/typeof.js")["default"];

var assertThisInitialized = __webpack_require__(/*! ./assertThisInitialized.js */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/setPrototypeOf.js":
/*!***************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/setPrototypeOf.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  module.exports["default"] = module.exports, module.exports.__esModule = true;
  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/slicedToArray.js":
/*!**************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/slicedToArray.js ***!
  \**************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayWithHoles = __webpack_require__(/*! ./arrayWithHoles.js */ "./node_modules/@babel/runtime/helpers/arrayWithHoles.js");

var iterableToArrayLimit = __webpack_require__(/*! ./iterableToArrayLimit.js */ "./node_modules/@babel/runtime/helpers/iterableToArrayLimit.js");

var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray.js */ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");

var nonIterableRest = __webpack_require__(/*! ./nonIterableRest.js */ "./node_modules/@babel/runtime/helpers/nonIterableRest.js");

function _slicedToArray(arr, i) {
  return arrayWithHoles(arr) || iterableToArrayLimit(arr, i) || unsupportedIterableToArray(arr, i) || nonIterableRest();
}

module.exports = _slicedToArray;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/toConsumableArray.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/toConsumableArray.js ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayWithoutHoles = __webpack_require__(/*! ./arrayWithoutHoles.js */ "./node_modules/@babel/runtime/helpers/arrayWithoutHoles.js");

var iterableToArray = __webpack_require__(/*! ./iterableToArray.js */ "./node_modules/@babel/runtime/helpers/iterableToArray.js");

var unsupportedIterableToArray = __webpack_require__(/*! ./unsupportedIterableToArray.js */ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js");

var nonIterableSpread = __webpack_require__(/*! ./nonIterableSpread.js */ "./node_modules/@babel/runtime/helpers/nonIterableSpread.js");

function _toConsumableArray(arr) {
  return arrayWithoutHoles(arr) || iterableToArray(arr) || unsupportedIterableToArray(arr) || nonIterableSpread();
}

module.exports = _toConsumableArray;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/typeof.js":
/*!*******************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/typeof.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return typeof obj;
    };

    module.exports["default"] = module.exports, module.exports.__esModule = true;
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };

    module.exports["default"] = module.exports, module.exports.__esModule = true;
  }

  return _typeof(obj);
}

module.exports = _typeof;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/unsupportedIterableToArray.js ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayLikeToArray = __webpack_require__(/*! ./arrayLikeToArray.js */ "./node_modules/@babel/runtime/helpers/arrayLikeToArray.js");

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(o);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return arrayLikeToArray(o, minLen);
}

module.exports = _unsupportedIterableToArray;
module.exports["default"] = module.exports, module.exports.__esModule = true;

/***/ }),

/***/ "./src/components/ShipmentsChart/ShipmentsChart.js":
/*!*********************************************************!*\
  !*** ./src/components/ShipmentsChart/ShipmentsChart.js ***!
  \*********************************************************/
/*! exports provided: ShipmentsChart */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ShipmentsChart", function() { return ShipmentsChart; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/date */ "@woocommerce/date");
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_date__WEBPACK_IMPORTED_MODULE_8__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }





var ShipmentsChart = /*#__PURE__*/function (_ReactComponent) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(ShipmentsChart, _ReactComponent);

  var _super = _createSuper(ShipmentsChart);

  function ShipmentsChart(props) {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ShipmentsChart);

    return _super.call(this, props);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ShipmentsChart, [{
    key: "render",
    value: function render() {
      var shipments_by_date = this.props.shipments_by_date;
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        class: "ts_analytics_graph"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["Chart"], {
        data: shipments_by_date,
        title: "Shipments  by Date",
        layout: "item-comparison",
        chartType: Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_8__["getChartTypeForQuery"])(this.props.query),
        legendPosition: "null"
      }));
    }
  }]);

  return ShipmentsChart;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

/***/ }),

/***/ "./src/components/ShipmentsTable/ShipmentsTable.js":
/*!*********************************************************!*\
  !*** ./src/components/ShipmentsTable/ShipmentsTable.js ***!
  \*********************************************************/
/*! exports provided: ShipmentsTable */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ShipmentsTable", function() { return ShipmentsTable; });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__);







function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_4___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_3___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }




var ShipmentsTable = /*#__PURE__*/function (_ReactComponent) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_2___default()(ShipmentsTable, _ReactComponent);

  var _super = _createSuper(ShipmentsTable);

  function ShipmentsTable(props) {
    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0___default()(this, ShipmentsTable);

    return _super.call(this, props);
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1___default()(ShipmentsTable, [{
    key: "render",
    value: function render() {
      var shipments = this.props.shipments_by_status;
      var shipments_by_providers = this.props.shipments_by_providers;
      var tableData = {
        shipments: shipments.map(function (item) {
          return [{
            display: item.shipment_status,
            value: item.shipment_status
          }, {
            display: item.total,
            value: item.total
          }, {
            display: item.percentage,
            value: item.percentage
          }];
        }),
        shipments_by_providers: shipments_by_providers.map(function (item) {
          return [{
            display: item.shipping_provider,
            value: item.shipping_provider
          }, {
            display: item.total,
            value: item.total
          }, {
            display: item.percentage,
            value: item.percentage
          }, {
            display: item.average,
            value: item.average
          }];
        })
      };
      return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])("div", {
        className: "woocommerce-dashboard__columns"
      }, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["TableCard"], {
        title: "Shipments by Status",
        headers: [{
          label: 'Tracking Status',
          key: 'shipment_status',
          isLeftAligned: true,
          required: true
        }, {
          label: 'Shipments',
          key: 'total',
          isLeftAligned: true
        }, {
          label: '% of total',
          key: 'percentage',
          isLeftAligned: true
        }],
        rows: tableData.shipments,
        rowsPerPage: 100,
        totalRows: tableData.shipments.length
      }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_7__["TableCard"], {
        title: "Shipments by Provider",
        headers: [{
          label: 'Shipping Provider',
          key: 'shipping_provider',
          isLeftAligned: true,
          required: true
        }, {
          label: 'Shipments',
          key: 'shipments',
          isLeftAligned: true
        }, {
          label: '% of total',
          key: 'total',
          isLeftAligned: true
        }, {
          label: 'Avg Length',
          key: 'average',
          isLeftAligned: true
        }],
        rows: tableData.shipments_by_providers,
        rowsPerPage: 100,
        totalRows: tableData.shipments_by_providers.length
      }));
    }
  }]);

  return ShipmentsTable;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__["Component"]);

/***/ }),

/***/ "./src/components/TSReport/TSReport.js":
/*!*********************************************!*\
  !*** ./src/components/TSReport/TSReport.js ***!
  \*********************************************/
/*! exports provided: TSReport */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "TSReport", function() { return TSReport; });
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/slicedToArray */ "./node_modules/@babel/runtime/helpers/slicedToArray.js");
/* harmony import */ var _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/createClass.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @babel/runtime/helpers/inherits */ "./node_modules/@babel/runtime/helpers/inherits.js");
/* harmony import */ var _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @babel/runtime/helpers/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @babel/runtime/helpers/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @woocommerce/components */ "@woocommerce/components");
/* harmony import */ var _woocommerce_components__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @woocommerce/date */ "@woocommerce/date");
/* harmony import */ var _woocommerce_date__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_date__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _woocommerce_currency__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @woocommerce/currency */ "@woocommerce/currency");
/* harmony import */ var _woocommerce_currency__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_currency__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var _ShipmentsChart_ShipmentsChart__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../ShipmentsChart/ShipmentsChart */ "./src/components/ShipmentsChart/ShipmentsChart.js");
/* harmony import */ var _ShipmentsTable_ShipmentsTable__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../ShipmentsTable/ShipmentsTable */ "./src/components/ShipmentsTable/ShipmentsTable.js");









function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _babel_runtime_helpers_getPrototypeOf__WEBPACK_IMPORTED_MODULE_6___default()(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _babel_runtime_helpers_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_5___default()(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

// src/index.js










var TSReport = /*#__PURE__*/function (_ReactComponent) {
  _babel_runtime_helpers_inherits__WEBPACK_IMPORTED_MODULE_4___default()(TSReport, _ReactComponent);

  var _super = _createSuper(TSReport);

  function TSReport(props) {
    var _this;

    _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1___default()(this, TSReport);

    _this = _super.call(this, props);
    _this.handleDateChange = _this.handleDateChange.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));
    _this.onFilterSelect = _this.onFilterSelect.bind(_babel_runtime_helpers_assertThisInitialized__WEBPACK_IMPORTED_MODULE_3___default()(_this));

    var dateQuery = _this.createDateQuery(_this.props.query);

    var storeCurrency = new _woocommerce_currency__WEBPACK_IMPORTED_MODULE_13___default.a(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_14__["CURRENCY"]);

    var queryParameters = _this.getQueryParameters(dateQuery);

    _this.state = {
      dateQuery: dateQuery,
      filterquery: '',
      currency: storeCurrency,
      data: {
        loading: true
      }
    };

    _this.fetchData(_this.state.dateQuery, _this.state.filterquery);

    return _this;
  }

  _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2___default()(TSReport, [{
    key: "fetchData",
    value: function fetchData(dateQuery, filterQuery) {
      var _this2 = this;

      var endPoints = {
        'shipments_count': '/wc/v3/ts-analytics/shipments_count',
        'shipments_by_status': '/wc/v3/ts-analytics/shipments_by_status',
        'shipments_by_providers': '/wc/v3/ts-analytics/shipments_by_providers',
        'shipments_by_date': '/wc/v3/ts-analytics/shipments_by_date',
        'get_shipments_providers': '/wc/v3/ts-analytics/get_shipments_providers'
      };
      var queryParameters = this.getQueryParameters(dateQuery);
      var shipments_count_path = endPoints.shipments_count + queryParameters + filterQuery;
      var shipments_status_path = endPoints.shipments_by_status + queryParameters + filterQuery;
      var shipments_by_providers_path = endPoints.shipments_by_providers + queryParameters + filterQuery;
      var shipments_by_date_path = endPoints.shipments_by_date + queryParameters + filterQuery;
      var shipments_providers_path = endPoints.get_shipments_providers;
      Promise.all([_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
        path: shipments_count_path
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
        path: shipments_status_path
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
        path: shipments_by_providers_path
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
        path: shipments_by_date_path
      }), _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_12___default()({
        path: shipments_providers_path
      })]).then(function (_ref) {
        var _ref2 = _babel_runtime_helpers_slicedToArray__WEBPACK_IMPORTED_MODULE_0___default()(_ref, 5),
            shipments_count = _ref2[0],
            shipments_by_status = _ref2[1],
            shipments_by_providers = _ref2[2],
            shipments_by_date = _ref2[3],
            shipments_providers = _ref2[4];

        var data = _this2.prepareData(shipments_count, shipments_by_status, shipments_by_providers, shipments_by_date, shipments_providers);

        _this2.setState({
          data: data
        });
      }).catch(function (err) {
        return console.log(err);
      });
    }
    /**
        * Parses a query and returns an object representing a date query that is used to handle date range changes.
        */

  }, {
    key: "createDateQuery",
    value: function createDateQuery(query) {
      var _getDateParamsFromQue = Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_11__["getDateParamsFromQuery"])(query),
          period = _getDateParamsFromQue.period,
          compare = _getDateParamsFromQue.compare,
          before = _getDateParamsFromQue.before,
          after = _getDateParamsFromQue.after;

      var _getCurrentDates = Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_11__["getCurrentDates"])(query),
          primaryDate = _getCurrentDates.primary,
          secondaryDate = _getCurrentDates.secondary;

      return {
        period: period,
        compare: compare,
        before: before,
        after: after,
        primaryDate: primaryDate,
        secondaryDate: secondaryDate
      };
    }
  }, {
    key: "getQueryParameters",
    value: function getQueryParameters(dateQuery) {
      var afterDate = encodeURIComponent(Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_11__["appendTimestamp"])(dateQuery.primaryDate.after, "start"));
      var beforeDate = encodeURIComponent(Object(_woocommerce_date__WEBPACK_IMPORTED_MODULE_11__["appendTimestamp"])(dateQuery.primaryDate.before, "end"));
      return "?after=".concat(afterDate, "&before=").concat(beforeDate, "&interval=day&order=asc&per_page=100&_locale=user");
    }
  }, {
    key: "prepareData",
    value: function prepareData(shipments_count, shipments_by_status, shipments_by_providers, shipments_by_date, shipments_providers) {
      var data;

      if (shipments_count['total_count'] && shipments_count['active_count'] && shipments_count['delivered_count'] && shipments_count['avg_shipping_length']) {
        data = {
          totals: {
            total_shipments: shipments_count['total_count'],
            active_shipments: shipments_count['active_count'],
            delivered_shipments: shipments_count['delivered_count'],
            avg_shipping_length: shipments_count['avg_shipping_length']
          }
        };
      } else {
        data = {
          totals: {
            total_shipments: 0,
            active_shipments: 0,
            delivered_shipments: 0,
            avg_shipping_length: 0
          }
        };
      }

      if (shipments_by_status.length > 0) {
        data.shipments_by_status = shipments_by_status;
      } else {
        data.shipments_by_status = [];
      }

      if (shipments_by_providers.length > 0) {
        data.shipments_by_providers = shipments_by_providers;
      } else {
        data.shipments_by_providers = [];
      }

      if (shipments_by_date.length > 0) {
        data.shipments_by_date = shipments_by_date;
      } else {
        data.shipments_by_date = [];
      }

      data.shipments_providers = shipments_providers;
      data.loading = false;
      return data;
    }
  }, {
    key: "handleDateChange",
    value: function handleDateChange(newQuery) {
      var newDateQuery = this.createDateQuery(newQuery);
      this.setState({
        dateQuery: newDateQuery
      });
      this.fetchData(newDateQuery, this.state.filterquery);
    }
  }, {
    key: "onFilterSelect",
    value: function onFilterSelect(filterdata) {
      if (filterdata.shipment_status) {
        var filterquery = '&shipment_status=' + filterdata.shipment_status;
        this.setState({
          filterquery: filterquery
        });
        this.fetchData(this.state.dateQuery, filterquery);
      } else if (filterdata.shipment_providers) {
        var _filterquery = '&shipment_provider=' + filterdata.shipment_providers;

        this.setState({
          filterquery: _filterquery
        });
        this.fetchData(this.state.dateQuery, _filterquery);
      } else {
        var _filterquery2 = '';
        this.setState({
          filterquery: _filterquery2
        });
        this.fetchData(this.state.dateQuery, '');
      }
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state = this.state,
          data = _this$state.data,
          currency = _this$state.currency,
          dateQuery = _this$state.dateQuery;
      var isRequesting = false;
      var shipment_status_data = [{
        label: 'All',
        value: 'all'
      }, {
        label: 'In Transit',
        value: 'in_transit'
      }, {
        label: 'Available For Pickup',
        value: 'available_for_pickup'
      }, {
        label: 'Out For Delivery',
        value: 'out_for_delivery'
      }, {
        label: 'Failed Attempt',
        value: 'failure'
      }, {
        label: 'On Hold',
        value: 'on_hold'
      }, {
        label: 'Exception',
        value: 'exception'
      }, {
        label: 'Return To Sender',
        value: 'return_to_sender'
      }, {
        label: 'Delivered',
        value: 'delivered'
      }];
      var ORDERS_REPORT_FILTERS_FILTER = 'woocommerce_admin_ts_analytics_report_filters';
      var filters = Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_9__["applyFilters"])(ORDERS_REPORT_FILTERS_FILTER, [{
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Shipment Status', 'woocommerce-admin'),
        staticParams: [],
        param: 'shipment_status',
        showFilters: function showFilters() {
          return true;
        },
        defaultValue: 'all',
        filters: shipment_status_data
      }, {
        label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Shipment Providers', 'woocommerce-admin'),
        staticParams: [],
        param: 'shipment_providers',
        showFilters: function showFilters() {
          return true;
        },
        defaultValue: 'all',
        filters: data.shipments_providers
      }]);
      var reportFilters = Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["ReportFilters"], {
        dateQuery: this.state.dateQuery,
        query: this.props.query,
        path: this.props.path,
        currency: this.state.currency,
        onDateSelect: this.handleDateChange,
        isoDateFormat: _woocommerce_date__WEBPACK_IMPORTED_MODULE_11__["isoDateFormat"],
        filters: filters,
        onFilterSelect: this.onFilterSelect
      });

      if (this.state.data.loading) {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Fragment"], null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["SummaryListPlaceholder"], {
          numberOfItems: 4
        }));
      } else {
        return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Fragment"], null, reportFilters, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["SummaryList"], null, function () {
          return [Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["SummaryNumber"], {
            key: "total_Shipments",
            value: data.totals.total_shipments,
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Total Shipments', 'trackship-for-woocommerce')
          }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["SummaryNumber"], {
            key: "active_Shipments",
            value: data.totals.active_shipments,
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Active Shipments', 'trackship-for-woocommerce')
          }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["SummaryNumber"], {
            key: "delivered_Shipments",
            value: data.totals.delivered_shipments,
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Delivered', 'trackship-for-woocommerce')
          }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_woocommerce_components__WEBPACK_IMPORTED_MODULE_8__["SummaryNumber"], {
            key: "avg_shipment_length",
            value: data.totals.avg_shipping_length,
            label: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_10__["__"])('Avg Shipment Length', 'trackship-for-woocommerce')
          })];
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_ShipmentsChart_ShipmentsChart__WEBPACK_IMPORTED_MODULE_15__["ShipmentsChart"], {
          chartData: 0,
          dateRange: dateQuery.primaryDate.range,
          shipments_by_date: data.shipments_by_date,
          currency: currency,
          query: this.props.query
        }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["createElement"])(_ShipmentsTable_ShipmentsTable__WEBPACK_IMPORTED_MODULE_16__["ShipmentsTable"], {
          shipments_by_status: data.shipments_by_status,
          shipments_by_providers: data.shipments_by_providers,
          dateRange: dateQuery.primaryDate.range,
          currency: currency
        }));
      }
    }
  }]);

  return TSReport;
}(_wordpress_element__WEBPACK_IMPORTED_MODULE_7__["Component"]);

/***/ }),

/***/ "./src/index.js":
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/toConsumableArray */ "./node_modules/@babel/runtime/helpers/toConsumableArray.js");
/* harmony import */ var _babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _index_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./index.scss */ "./src/index.scss");
/* harmony import */ var _index_scss__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_index_scss__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _components_TSReport_TSReport__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/TSReport/TSReport */ "./src/components/TSReport/TSReport.js");

// Import SCSS entry file so that webpack picks up changes




Object(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__["addFilter"])('woocommerce_admin_reports_list', 'trackship-analytics', function (reports) {
  return [].concat(_babel_runtime_helpers_toConsumableArray__WEBPACK_IMPORTED_MODULE_0___default()(reports), [{
    report: 'trackship-analytics',
    title: Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__["__"])('TrackShip Analytics', 'trackship-for-woocommerce'),
    component: _components_TSReport_TSReport__WEBPACK_IMPORTED_MODULE_4__["TSReport"]
  }]);
});

/***/ }),

/***/ "./src/index.scss":
/*!************************!*\
  !*** ./src/index.scss ***!
  \************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "@woocommerce/components":
/*!************************************!*\
  !*** external ["wc","components"] ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["components"]; }());

/***/ }),

/***/ "@woocommerce/currency":
/*!**********************************!*\
  !*** external ["wc","currency"] ***!
  \**********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["currency"]; }());

/***/ }),

/***/ "@woocommerce/date":
/*!******************************!*\
  !*** external ["wc","date"] ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["date"]; }());

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wc"]["wcSettings"]; }());

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["apiFetch"]; }());

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ }),

/***/ "@wordpress/hooks":
/*!*******************************!*\
  !*** external ["wp","hooks"] ***!
  \*******************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["hooks"]; }());

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ })

/******/ });
//# sourceMappingURL=index.js.map