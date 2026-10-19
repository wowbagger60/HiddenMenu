const assert=require('node:assert/strict');
const height=require('../HiddenMenu/layout.js');
const cases=[
 ['body margin plus host margin',700,24,700,true,660],
 ['fullscreen with header offset',700,80,700,false,604],
 ['parent taller than viewport',700,80,1200,false,604],
 ['small tile bounds',700,30,250,false,218],
 ['mobile visible viewport',500,24,700,true,460],
 ['very small viewport',30,24,30,true,0],
 ['fractional pixels cannot overflow',700,24.5,700,true,659]
];
for(const [name,bottom,top,parent,body,expected] of cases){const actual=height(bottom,top,parent,body);assert.equal(actual,expected,name);if(top<bottom)assert(top+actual<=Math.max(top,bottom-16));console.log('PASS '+name);}
console.log(cases.length+' layout regression tests passed.');
