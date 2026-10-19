const assert=require('node:assert/strict');
const Gesture=require('../HiddenMenu/gestures.js');
function setup(sequence){
 let now=0,id=0,opened=0;const jobs=new Map();
 const clock={setTimeout(fn,ms){jobs.set(++id,{fn,at:now+ms});return id;},clearTimeout(i){jobs.delete(i);}};
 const g=new Gesture({gesture:sequence,tapGap:350,holdTime:800,stepTimeout:2500,swipeDistance:60},()=>opened++,clock);
 function wait(ms){const end=now+ms;while(true){const a=[...jobs].filter(([i,j])=>j.at<=end).sort((a,b)=>a[1].at-b[1].at)[0];if(!a)break;now=a[1].at;jobs.delete(a[0]);a[1].fn();}now=end;}
 function contact(dx=0,dy=0,ms=60){g.down(1,100,100,now);wait(ms);g.move(1,100+dx,100+dy);g.up(1,100+dx,100+dy,now);}
 return {g,wait,contact,get opened(){return opened;}};
}
let count=0;function test(name,fn){fn();count++;console.log('PASS '+name);}
for(let n=1;n<=5;n++)test(n+' taps',()=>{const x=setup(['tap'+n]);for(let i=0;i<n;i++){x.contact();x.wait(100);}assert.equal(x.opened,1);x.wait(350);assert.equal(x.opened,1);});
test('no early triple at second tap',()=>{const x=setup(['tap3']);x.contact();x.wait(100);x.contact();x.wait(350);assert.equal(x.opened,0);});
test('configured count opens once without waiting for extra taps',()=>{const x=setup(['tap3']);for(let i=0;i<4;i++){x.contact();x.wait(80);}x.wait(400);assert.equal(x.opened,1);});
for(const [dir,dx,dy] of [['up',0,-90],['down',0,90],['left',-90,0],['right',90,0],['upLeft',-90,-90],['upRight',90,-90],['downLeft',-90,90],['downRight',90,90]])test(dir,()=>{const x=setup([dir]);x.contact(dx,dy,200);assert.equal(x.opened,1);});
test('hold',()=>{const x=setup(['hold']);x.contact(0,0,850);assert.equal(x.opened,1);});
test('short hold rejected',()=>{const x=setup(['hold']);x.contact(0,0,600);x.wait(400);assert.equal(x.opened,0);});
test('triple then down',()=>{const x=setup(['tap3','down']);for(let i=0;i<3;i++){x.contact();x.wait(100);}x.wait(500);x.contact(0,100,200);assert.equal(x.opened,1);});
test('wrong step resets',()=>{const x=setup(['tap1','down']);x.contact();x.wait(400);x.contact(100,0);x.contact(0,100);assert.equal(x.opened,0);});
test('sequence timeout',()=>{const x=setup(['tap1','down']);x.contact();x.wait(3000);x.contact(0,100);assert.equal(x.opened,0);});
test('cancel clears pending tap',()=>{const x=setup(['tap3']);x.contact();x.g.reset();x.wait(400);assert.equal(x.opened,0);});
test('moving away and returning is not tap',()=>{const x=setup(['tap1']);x.g.down(1,0,0,0);x.g.move(1,100,100);x.g.up(1,0,0,200);x.wait(500);assert.equal(x.opened,0);});
test('multitouch resets',()=>{const x=setup(['tap1']);x.g.down(1,0,0,0);assert.equal(x.g.down(2,0,0,10),false);x.g.up(1,0,0,20);x.wait(500);assert.equal(x.opened,0);});
console.log(count+' gesture tests passed.');
