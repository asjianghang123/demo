/* 百度地图API V2 模块
 * 此模块必须配套使用baidumap_offline_v2_20160822.js对
 * 获取模块的方法：
 * http://api0.map.bdimg.com/getmodules?v=2.0&mod=模块1,模块2
 * 模块名称就是文件名
 * www.xiaoguo123.com 整理
 */
 _jsload2&&_jsload2('copyrightctrl', 'x.extend(Xb.prototype,{uf:function(){this.C&&this.Ce(this.C)},initialize:function(a){Sb.prototype.initialize.call(this,a);this.za();this.qo();this.ca(a);return this.B},ca:function(a){var b=this;a.addEventListener("load",function(){b.qo()});a.addEventListener("moveend",function(){b.qo()});a.addEventListener("zoomend",function(){b.qo()});a.addEventListener("maptypechange",function(){b.B&&(b.B.style.color=b.C.oa().wm())})},za:function(){Sb.prototype.za.call(this);x.D.Ta(this.B,"BMap_cpyCtrl");var a= this.B.style;a.cursor="default";a.whiteSpace="nowrap";a.MozUserSelect="none";a.color=this.C.oa().wm();a.background="none";a.font="11px/15px "+F.fontFamily;Sb.prototype.Jr.call(this)},qo:function(){if(this.C&&this.B&&0!=this.cc.length)for(var a=0,b=this.cc.length;a<b;a++){this.C.fa();var c=this.C.ub({x:0,y:0}),d=this.C.ub({x:this.C.width,y:this.C.height}),c=new fb(c,d);if(this.cc[a].bounds&&c.ft(this.cc[a].bounds)==p){if(this.B)for(d=0;d<this.B.children.length;d++)if(this.B.children[d].getAttribute("_cid")== this.cc[a].id&&"none"!=this.B.children[d].style.display){this.B.children[d].style.display="none";return}}else if(this.B){for(var c=q,d=0,e=this.B.children.length;d<e;d++)if(this.B.children[d].getAttribute("_cid")==this.cc[a].id){c=o;this.B.children[d].style.display="inline";this.B.children[d].innerHTML!=this.cc[a].content&&(this.B.children[d].innerHTML=this.cc[a].content);break}c||this.Qq(this.cc[a])}}},mw:function(a){if(a&&Va(a.id)&&!isNaN(a.id)){var b={bounds:p,content:""},c;for(c in a)b[c]=a[c]; if(a=this.om(a.id))for(var d in b)a[d]=b[d];else this.cc.push(b);this.qo()}},om:function(a){for(var b=0,c=this.cc.length;b<c;b++)if(this.cc[b].id==a)return this.cc[b]},FD:t("cc"),cF:function(a){for(var b,c=0,d=this.cc.length;c<d;c++)this.cc[c].id==a&&(b=this.cc.splice(c,1),c--,d=this.cc.length);(a=this.Hd(a))&&a.parentNode&&a.parentNode.removeChild(a);this.qo();return b},Qq:function(a){this.B&&(this.B.innerHTML+="<span _cid=\'"+a.id+"\'>"+a.content+"</span>")},Hd:function(a){var b=Sb.prototype.Hd.call(this); if(Gb(a)){if(b)for(var c=0,d=b.children.length;c<d;c++)if(b.children[c].getAttribute("_cid")==a)return b.children[c]}else return b}});S(ef,{addCopyright:ef.mw,removeCopyright:ef.cF,getCopyright:ef.om,getCopyrightCollection:ef.FD}); ');
