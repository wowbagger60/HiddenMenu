'use strict';
function hiddenMenuColor(value){
  const n=Number(value);
  return Number.isInteger(n)&&n>=0&&n<=0xffffff ? '#'+n.toString(16).padStart(6,'0') : '';
}
function hiddenMenuIcon(value){
  const name=String(value||'').trim().toLowerCase();
  return name==='transparent'||! /^[a-z0-9][a-z0-9-]*$/.test(name) ? '' : name;
}
function hiddenMenuInk(value){
  const n=Number(value),r=(n>>16)&255,g=(n>>8)&255,b=n&255;
  // Same threshold as ListBox getTextColor().
  const brightness=.299*r+.587*g+.114*b;
  return brightness<189?'#ffffff':'#000000';
}
function hiddenMenuTouchAction(gestures){
  // Touch scrolling is only allowed along axes that no configured swipe uses.
  const list=Array.isArray(gestures)?gestures:[];
  const vertical=list.some(g=>/^(up|down)/.test(g));
  const horizontal=list.some(g=>/^(left|right)$|Left$|Right$/.test(g));
  return !vertical&&!horizontal?'pan-x pan-y':!vertical?'pan-y':!horizontal?'pan-x':'none';
}
if(typeof module!=='undefined'&&module.exports)module.exports={hiddenMenuColor,hiddenMenuIcon,hiddenMenuInk,hiddenMenuTouchAction};
