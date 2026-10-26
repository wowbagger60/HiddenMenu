const assert=require('node:assert/strict');
const HiddenGesture=require('../HiddenMenu/gestures.js');
const HiddenInput=require('../HiddenMenu/input.js');
function setup(sequence){
 const win=new EventTarget(),doc=new EventTarget(),surface=new EventTarget();
 doc.defaultView=win;surface.ownerDocument=doc;surface.closest=()=>null;
 let opened=0;const jobs=new Map();let id=0;
 const clock={setTimeout(fn){jobs.set(++id,fn);return id;},clearTimeout(i){jobs.delete(i);}};
 const engine=new HiddenGesture({gesture:sequence,tapGap:600,holdTime:800,stepTimeout:2500,swipeDistance:60},()=>opened++,clock);
 const input=new HiddenInput(surface,engine,()=>true);
 let t=0;
 function send(target,type,props={}){
   const e=new Event(type,{cancelable:true,bubbles:true});
   Object.defineProperty(e,'timeStamp',{value:t+=60});
   Object.assign(e,{button:0,clientX:100,clientY:100,...props});target.dispatchEvent(e);return e;
 }
 function mouseTap(){send(surface,'mousedown');send(doc,'mouseup');}
 function touchTap(){const touch={identifier:1,clientX:100,clientY:100};send(surface,'touchstart',{touches:[touch],changedTouches:[touch]});send(surface,'touchend',{touches:[],changedTouches:[touch]});}
 return {surface,doc,win,engine,input,send,mouseTap,touchTap,get opened(){return opened;}};
}
let count=0;function test(name,fn){fn();console.log('PASS '+name);count++;}
for(let n=1;n<=5;n++){
 test('mouse events: '+n+' clicks',()=>{const x=setup(['tap'+n]);for(let i=0;i<n;i++)x.mouseTap();assert.equal(x.opened,1);x.input.destroy();});
 test('touch events: '+n+' taps',()=>{const x=setup(['tap'+n]);for(let i=0;i<n;i++)x.touchTap();assert.equal(x.opened,1);x.input.destroy();});
}
for(const [direction,dx,dy] of [['up',0,-100],['down',0,100],['left',-100,0],['right',100,0],['upLeft',-100,-100],['upRight',100,-100],['downLeft',-100,100],['downRight',100,100]]){
 test('mouse drag '+direction+' including release outside surface',()=>{const x=setup([direction]);x.send(x.surface,'mousedown');x.send(x.doc,'mousemove',{clientX:100+dx,clientY:100+dy});x.send(x.doc,'mouseup',{clientX:100+dx,clientY:100+dy});assert.equal(x.opened,1);x.input.destroy();});
}
test('triple click then drag',()=>{const x=setup(['tap3','down']);for(let i=0;i<3;i++)x.mouseTap();assert.equal(x.opened,0);x.send(x.surface,'mousedown');x.send(x.doc,'mouseup',{clientY:220});assert.equal(x.opened,1);x.input.destroy();});
test('touch does not count synthetic mouse twice',()=>{const x=setup(['tap3']);x.touchTap();x.mouseTap();x.touchTap();assert.equal(x.opened,0);x.touchTap();assert.equal(x.opened,1);x.input.destroy();});
test('touch cancellation clears progress',()=>{const x=setup(['tap3']);x.touchTap();x.send(x.surface,'touchcancel');x.touchTap();x.touchTap();assert.equal(x.opened,0);x.input.destroy();});
test('host double-click default is prevented',()=>{const x=setup(['tap3']);const e=x.send(x.surface,'dblclick');assert(e.defaultPrevented);x.input.destroy();});
test('destroy removes old event handlers',()=>{const x=setup(['tap1']);x.input.destroy();x.mouseTap();assert.equal(x.opened,0);});
test('legacy data-hiddm-passive attribute is ignored for clicks',()=>{
 const x=setup(['tap3']);
 x.surface.closest=selector=>selector.includes('[data-hiddm-passive]')?x.surface:null;
 for(let i=0;i<3;i++)x.mouseTap();assert.equal(x.opened,1);x.input.destroy();
});
test('legacy data-hiddm-passive attribute is ignored for taps',()=>{
 const x=setup(['tap3']);
 x.surface.closest=selector=>selector.includes('[data-hiddm-passive]')?x.surface:null;
 for(let i=0;i<3;i++)x.touchTap();assert.equal(x.opened,1);x.input.destroy();
});
test('browser text selection is prevented',()=>{
 const x=setup(['tap3']);const e=x.send(x.surface,'selectstart');assert(e.defaultPrevented);x.input.destroy();
});
console.log(count+' input integration tests passed.');
