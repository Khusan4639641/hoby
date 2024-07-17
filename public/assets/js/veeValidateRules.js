/**
 * vee-validate v3.4.14
 * (c) 2021 Abdelrahman Awad
 * @license MIT
 */
!function(r,e){"object"==typeof exports&&"undefined"!=typeof module?e(exports):"function"==typeof define&&define.amd?define(["exports"],e):e((r="undefined"!=typeof globalThis?globalThis:r||self).VeeValidateRules={})}(this,(function(r){"use strict";var e={en:/^[A-Z]*$/i,cs:/^[A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ]*$/i,da:/^[A-ZÆØÅ]*$/i,de:/^[A-ZÄÖÜß]*$/i,es:/^[A-ZÁÉÍÑÓÚÜ]*$/i,fa:/^[ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىيًٌٍَُِّْٰپژگچکی]*$/,fr:/^[A-ZÀÂÆÇÉÈÊËÏÎÔŒÙÛÜŸ]*$/i,it:/^[A-Z\xC0-\xFF]*$/i,lt:/^[A-ZĄČĘĖĮŠŲŪŽ]*$/i,nl:/^[A-ZÉËÏÓÖÜ]*$/i,hu:/^[A-ZÁÉÍÓÖŐÚÜŰ]*$/i,pl:/^[A-ZĄĆĘŚŁŃÓŻŹ]*$/i,pt:/^[A-ZÃÁÀÂÇÉÊÍÕÓÔÚÜ]*$/i,ro:/^[A-ZĂÂÎŞŢ]*$/i,ru:/^[А-ЯЁ]*$/i,sk:/^[A-ZÁÄČĎÉÍĹĽŇÓŔŠŤÚÝŽ]*$/i,sr:/^[A-ZČĆŽŠĐ]*$/i,sv:/^[A-ZÅÄÖ]*$/i,tr:/^[A-ZÇĞİıÖŞÜ]*$/i,uk:/^[А-ЩЬЮЯЄІЇҐ]*$/i,ar:/^[ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىيًٌٍَُِّْٰ]*$/,az:/^[A-ZÇƏĞİıÖŞÜ]*$/i,el:/^[Α-ώ]*$/i,ja:/^[A-Z\u3000-\u303F\u3040-\u309F\u30A0-\u30FF\uFF00-\uFFEF\u4E00-\u9FAF]*$/i,he:/^[A-Z\u05D0-\u05EA']*$/i},t={en:/^[A-Z\s]*$/i,cs:/^[A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ\s]*$/i,da:/^[A-ZÆØÅ\s]*$/i,de:/^[A-ZÄÖÜß\s]*$/i,es:/^[A-ZÁÉÍÑÓÚÜ\s]*$/i,fa:/^[ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىيًٌٍَُِّْٰپژگچکی]*$/,fr:/^[A-ZÀÂÆÇÉÈÊËÏÎÔŒÙÛÜŸ\s]*$/i,it:/^[A-Z\xC0-\xFF\s]*$/i,lt:/^[A-ZĄČĘĖĮŠŲŪŽ\s]*$/i,nl:/^[A-ZÉËÏÓÖÜ\s]*$/i,hu:/^[A-ZÁÉÍÓÖŐÚÜŰ\s]*$/i,pl:/^[A-ZĄĆĘŚŁŃÓŻŹ\s]*$/i,pt:/^[A-ZÃÁÀÂÇÉÊÍÕÓÔÚÜ\s]*$/i,ro:/^[A-ZĂÂÎŞŢ\s]*$/i,ru:/^[А-ЯЁ\s]*$/i,sk:/^[A-ZÁÄČĎÉÍĹĽŇÓŔŠŤÚÝŽ\s]*$/i,sr:/^[A-ZČĆŽŠĐ\s]*$/i,sv:/^[A-ZÅÄÖ\s]*$/i,tr:/^[A-ZÇĞİıÖŞÜ\s]*$/i,uk:/^[А-ЩЬЮЯЄІЇҐ\s]*$/i,ar:/^[ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىيًٌٍَُِّْٰ\s]*$/,az:/^[A-ZÇƏĞİıÖŞÜ\s]*$/i,el:/^[Α-ώ\s]*$/i,ja:/^[A-Z\u3000-\u303F\u3040-\u309F\u30A0-\u30FF\uFF00-\uFFEF\u4E00-\u9FAF\s]*$/i,he:/^[A-Z\u05D0-\u05EA'\s]*$/i},n={en:/^[0-9A-Z]*$/i,cs:/^[0-9A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ]*$/i,da:/^[0-9A-ZÆØÅ]$/i,de:/^[0-9A-ZÄÖÜß]*$/i,es:/^[0-9A-ZÁÉÍÑÓÚÜ]*$/i,fa:/^[ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىيًٌٍَُِّْٰپژگچکی]*$/,fr:/^[0-9A-ZÀÂÆÇÉÈÊËÏÎÔŒÙÛÜŸ]*$/i,it:/^[0-9A-Z\xC0-\xFF]*$/i,lt:/^[0-9A-ZĄČĘĖĮŠŲŪŽ]*$/i,hu:/^[0-9A-ZÁÉÍÓÖŐÚÜŰ]*$/i,nl:/^[0-9A-ZÉËÏÓÖÜ]*$/i,pl:/^[0-9A-ZĄĆĘŚŁŃÓŻŹ]*$/i,pt:/^[0-9A-ZÃÁÀÂÇÉÊÍÕÓÔÚÜ]*$/i,ro:/^[0-9A-ZĂÂÎŞŢ]*$/i,ru:/^[0-9А-ЯЁ]*$/i,sk:/^[0-9A-ZÁÄČĎÉÍĹĽŇÓŔŠŤÚÝŽ]*$/i,sr:/^[0-9A-ZČĆŽŠĐ]*$/i,sv:/^[0-9A-ZÅÄÖ]*$/i,tr:/^[0-9A-ZÇĞİıÖŞÜ]*$/i,uk:/^[0-9А-ЩЬЮЯЄІЇҐ]*$/i,ar:/^[٠١٢٣٤٥٦٧٨٩0-9ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىيًٌٍَُِّْٰ]*$/,az:/^[0-9A-ZÇƏĞİıÖŞÜ]*$/i,el:/^[0-9Α-ώ]*$/i,ja:/^[0-9A-Z\u3000-\u303F\u3040-\u309F\u30A0-\u30FF\uFF00-\uFFEF\u4E00-\u9FAF]*$/i,he:/^[0-9A-Z\u05D0-\u05EA']*$/i},i={en:/^[0-9A-Z_-]*$/i,cs:/^[0-9A-ZÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ_-]*$/i,da:/^[0-9A-ZÆØÅ_-]*$/i,de:/^[0-9A-ZÄÖÜß_-]*$/i,es:/^[0-9A-ZÁÉÍÑÓÚÜ_-]*$/i,fa:/^[ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىيًٌٍَُِّْٰپژگچکی]*$/,fr:/^[0-9A-ZÀÂÆÇÉÈÊËÏÎÔŒÙÛÜŸ_-]*$/i,it:/^[0-9A-Z\xC0-\xFF_-]*$/i,lt:/^[0-9A-ZĄČĘĖĮŠŲŪŽ_-]*$/i,nl:/^[0-9A-ZÉËÏÓÖÜ_-]*$/i,hu:/^[0-9A-ZÁÉÍÓÖŐÚÜŰ_-]*$/i,pl:/^[0-9A-ZĄĆĘŚŁŃÓŻŹ_-]*$/i,pt:/^[0-9A-ZÃÁÀÂÇÉÊÍÕÓÔÚÜ_-]*$/i,ro:/^[0-9A-ZĂÂÎŞŢ_-]*$/i,ru:/^[0-9А-ЯЁ_-]*$/i,sk:/^[0-9A-ZÁÄČĎÉÍĹĽŇÓŔŠŤÚÝŽ_-]*$/i,sr:/^[0-9A-ZČĆŽŠĐ_-]*$/i,sv:/^[0-9A-ZÅÄÖ_-]*$/i,tr:/^[0-9A-ZÇĞİıÖŞÜ_-]*$/i,uk:/^[0-9А-ЩЬЮЯЄІЇҐ_-]*$/i,ar:/^[٠١٢٣٤٥٦٧٨٩0-9ءآأؤإئابةتثجحخدذرزسشصضطظعغفقكلمنهوىيًٌٍَُِّْٰ_-]*$/,az:/^[0-9A-ZÇƏĞİıÖŞÜ_-]*$/i,el:/^[0-9Α-ώ_-]*$/i,ja:/^[0-9A-Z\u3000-\u303F\u3040-\u309F\u30A0-\u30FF\uFF00-\uFFEF\u4E00-\u9FAF_-]*$/i,he:/^[0-9A-Z\u05D0-\u05EA'_-]*$/i},a=function(r,t){var n=(void 0===t?{}:t).locale,i=void 0===n?"":n;return Array.isArray(r)?r.every((function(r){return a(r,{locale:i})})):i?(e[i]||e.en).test(r):Object.keys(e).some((function(t){return e[t].test(r)}))},u={validate:a,params:[{name:"locale"}]},s=function(r,e){var t=(void 0===e?{}:e).locale,n=void 0===t?"":t;return Array.isArray(r)?r.every((function(r){return s(r,{locale:n})})):n?(i[n]||i.en).test(r):Object.keys(i).some((function(e){return i[e].test(r)}))},o={validate:s,params:[{name:"locale"}]},l=function(r,e){var t=(void 0===e?{}:e).locale,i=void 0===t?"":t;return Array.isArray(r)?r.every((function(r){return l(r,{locale:i})})):i?(n[i]||n.en).test(r):Object.keys(n).some((function(e){return n[e].test(r)}))},A={validate:l,params:[{name:"locale"}]},c=function(r,e){var n=(void 0===e?{}:e).locale,i=void 0===n?"":n;return Array.isArray(r)?r.every((function(r){return c(r,{locale:i})})):i?(t[i]||t.en).test(r):Object.keys(t).some((function(e){return t[e].test(r)}))},f={validate:c,params:[{name:"locale"}]},m=function(r,e){var t=void 0===e?{}:e,n=t.min,i=t.max;return Array.isArray(r)?r.every((function(r){return!!m(r,{min:n,max:i})})):Number(n)<=r&&Number(i)>=r},v={validate:m,params:[{name:"min"},{name:"max"}]},$={validate:function(r,e){var t=e.target;return String(r)===String(t)},params:[{name:"target",isTarget:!0}]},d=function(r,e){var t=e.length;if(Array.isArray(r))return r.every((function(r){return d(r,{length:t})}));var n=String(r);return/^[0-9]*$/.test(n)&&n.length===t},y={validate:d,params:[{name:"length",cast:function(r){return Number(r)}}]},Z={validate:function(r,e){var t=e.width,n=e.height,i=[];r=Array.isArray(r)?r:[r];for(var a=0;a<r.length;a++){if(!/\.(jpg|svg|jpeg|png|bmp|gif)$/i.test(r[a].name))return Promise.resolve(!1);i.push(r[a])}return Promise.all(i.map((function(r){return function(r,e,t){var n=window.URL||window.webkitURL;return new Promise((function(i){var a=new Image;a.onerror=function(){return i(!1)},a.onload=function(){return i(a.width===e&&a.height===t)},a.src=n.createObjectURL(r)}))}(r,t,n)}))).then((function(r){return r.every((function(r){return r}))}))},params:[{name:"width",cast:function(r){return Number(r)}},{name:"height",cast:function(r){return Number(r)}}]},g={validate:function(r,e){var t=(void 0===e?{}:e).multiple,n=/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;return t&&!Array.isArray(r)&&(r=String(r).split(",").map((function(r){return r.trim()}))),Array.isArray(r)?r.every((function(r){return n.test(String(r))})):n.test(String(r))},params:[{name:"multiple",default:!1}]};function p(r){return null==r}function h(r){return Array.isArray(r)&&0===r.length}function F(r){return"function"==typeof Array.from?Array.from(r):function(r){for(var e=[],t=r.length,n=0;n<t;n++)e.push(r[n]);return e}(r)}var _=function(r,e){return Array.isArray(r)?r.every((function(r){return _(r,e)})):F(e).some((function(e){return e==r}))},x={validate:_},b={validate:function(r,e){return!_(r,e)}},w={validate:function(r,e){var t=new RegExp(".("+e.join("|")+")$","i");return Array.isArray(r)?r.every((function(r){return t.test(r.name)})):t.test(r.name)}},S={validate:function(r){var e=/\.(jpg|svg|jpeg|png|bmp|gif|webp)$/i;return Array.isArray(r)?r.every((function(r){return e.test(r.name)})):e.test(r.name)}},j={validate:function(r){return Array.isArray(r)?r.every((function(r){return/^-?[0-9]+$/.test(String(r))})):/^-?[0-9]+$/.test(String(r))}},E={validate:function(r,e){return r===e.other},params:[{name:"other"}]},N={validate:function(r,e){return r!==e.other},params:[{name:"other"}]},k={validate:function(r,e){var t=e.length;return!p(r)&&("string"==typeof r&&(r=F(r)),"number"==typeof r&&(r=String(r)),r.length||(r=F(r)),r.length===t)},params:[{name:"length",cast:function(r){return Number(r)}}]},z=function(r,e){var t=e.length;return p(r)?t>=0:Array.isArray(r)?r.every((function(r){return z(r,{length:t})})):String(r).length<=t},R={validate:z,params:[{name:"length",cast:function(r){return Number(r)}}]},O=function(r,e){var t=e.max;return!p(r)&&""!==r&&(Array.isArray(r)?r.length>0&&r.every((function(r){return O(r,{max:t})})):Number(r)<=t)},q={validate:O,params:[{name:"max",cast:function(r){return Number(r)}}]},C={validate:function(r,e){var t=new RegExp(e.join("|").replace("*",".+")+"$","i");return Array.isArray(r)?r.every((function(r){return t.test(r.type)})):t.test(r.type)}},D=function(r,e){var t=e.length;return!p(r)&&(Array.isArray(r)?r.every((function(r){return D(r,{length:t})})):String(r).length>=t)},P={validate:D,params:[{name:"length",cast:function(r){return Number(r)}}]},T=function(r,e){var t=e.min;return!p(r)&&""!==r&&(Array.isArray(r)?r.length>0&&r.every((function(r){return T(r,{min:t})})):Number(r)>=t)},L={validate:T,params:[{name:"min",cast:function(r){return Number(r)}}]},U=/^[٠١٢٣٤٥٦٧٨٩]+$/,V=/^[0-9]+$/,I={validate:function(r){var e=function(r){var e=String(r);return V.test(e)||U.test(e)};return Array.isArray(r)?r.every(e):e(r)}},M=function(r,e){var t=e.regex;return Array.isArray(r)?r.every((function(r){return M(r,{regex:t})})):t.test(String(r))},B={validate:M,params:[{name:"regex",cast:function(r){return"string"==typeof r?new RegExp(r):r}}]},G={validate:function(r,e){var t=(void 0===e?{allowFalse:!0}:e).allowFalse,n={valid:!1,required:!0};return p(r)||h(r)?n:!1!==r||t?(n.valid=!!String(r).trim().length,n):n},params:[{name:"allowFalse",default:!0}],computesRequired:!0},H=function(r){return h(r)||-1!==[!1,null,void 0].indexOf(r)||!String(r).trim().length},J={validate:function(r,e){var t,n=e.target,i=e.values;return i&&i.length?(Array.isArray(i)||"string"!=typeof i||(i=[i]),t=i.some((function(r){return r==String(n).trim()}))):t=!H(n),t?{valid:!H(r),required:t}:{valid:!0,required:t}},params:[{name:"target",isTarget:!0},{name:"values"}],computesRequired:!0},K={validate:function(r,e){var t=e.size;if(isNaN(t))return!1;var n=1024*t;if(!Array.isArray(r))return r.size<=n;for(var i=0;i<r.length;i++)if(r[i].size>n)return!1;return!0},params:[{name:"size",cast:function(r){return Number(r)}}]},Q={validate:function(r,e){var t=e||{},n=t.decimals,i=void 0===n?0:n,a=t.separator,u=new RegExp("^-?\\d+"+("comma"===(void 0===a?"dot":a)?",?":"\\.?")+(0===i?"\\d*":"(\\d{"+i+"})?")+"$");return Array.isArray(r)?r.every((function(r){return u.test(String(r))})):u.test(String(r))},params:[{name:"decimals",default:0},{name:"separator",default:"dot"}]};r.alpha=u,r.alpha_dash=o,r.alpha_num=A,r.alpha_spaces=f,r.between=v,r.confirmed=$,r.digits=y,r.dimensions=Z,r.double=Q,r.email=g,r.excluded=b,r.ext=w,r.image=S,r.integer=j,r.is=E,r.is_not=N,r.length=k,r.max=R,r.max_value=q,r.mimes=C,r.min=P,r.min_value=L,r.numeric=I,r.oneOf=x,r.regex=B,r.required=G,r.required_if=J,r.size=K,Object.defineProperty(r,"__esModule",{value:!0})}));
