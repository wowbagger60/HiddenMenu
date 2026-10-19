'use strict';
class HiddenInput {
  constructor(surface, engine, enabled, ownerDocument = surface.ownerDocument, win = ownerDocument.defaultView) {
    this.surface=surface; this.engine=engine; this.enabled=enabled; this.doc=ownerDocument;this.win=win;
    this.mouse=false;this.touch=null;this.blocked=false;this.ignoreMouseUntil=0;this.listeners=[];
    const on=(target,name,fn,options={})=>{target.addEventListener(name,fn,options);this.listeners.push(()=>target.removeEventListener(name,fn,options));};
    const interactive=e=>e.target.closest?.('button,a,input,select,textarea,summary,[contenteditable="true"]');
    const stop=e=>{e.stopPropagation();if(e.cancelable)e.preventDefault();};
    // Keep host tile gestures from interpreting our contacts as drag/double click.
    for(const name of ['pointerdown','pointermove','pointerup','click','dblclick','dragstart','contextmenu','selectstart']) {
      on(surface,name,e=>{e.stopPropagation();if(!interactive(e)&&['dblclick','dragstart','contextmenu','selectstart'].includes(name)&&e.cancelable)e.preventDefault();});
    }
    on(surface,'mousedown',e=>{
      if(e.button!==0||!enabled()||interactive(e)||Date.now()<this.ignoreMouseUntil)return;
      stop(e);this.mouse=true;engine.down('mouse',e.clientX,e.clientY,e.timeStamp);
    });
    on(ownerDocument,'mousemove',e=>{if(this.mouse){stop(e);engine.move('mouse',e.clientX,e.clientY);}},true);
    on(ownerDocument,'mouseup',e=>{
      if(!this.mouse||e.button!==0)return;
      stop(e);this.mouse=false;engine.up('mouse',e.clientX,e.clientY,e.timeStamp);
    },true);
    on(surface,'touchstart',e=>{
      if(!enabled()||interactive(e))return;
      stop(e);this.ignoreMouseUntil=Date.now()+1000;
      if(e.touches.length!==1||this.touch!==null){this.blocked=true;this.touch=null;engine.reset();return;}
      if(this.blocked)return;
      const t=e.changedTouches[0];this.touch=t.identifier;
      engine.down(t.identifier,t.clientX,t.clientY,e.timeStamp);
    },{passive:false});
    on(surface,'touchmove',e=>{
      if(this.touch===null&&!this.blocked)return;stop(e);
      const t=Array.from(e.touches).find(t=>t.identifier===this.touch);
      if(t&&!this.blocked)engine.move(t.identifier,t.clientX,t.clientY);
    },{passive:false});
    on(surface,'touchend',e=>{
      stop(e);this.ignoreMouseUntil=Date.now()+1000;
      const t=Array.from(e.changedTouches).find(t=>t.identifier===this.touch);
      if(t&&!this.blocked){this.touch=null;engine.up(t.identifier,t.clientX,t.clientY,e.timeStamp);}
      if(!e.touches.length){this.blocked=false;this.touch=null;}
    },{passive:false});
    on(surface,'touchcancel',()=>this.reset());
    on(win,'blur',()=>this.reset());
    on(ownerDocument,'visibilitychange',()=>{if(ownerDocument.hidden)this.reset();});
  }
  reset(){this.mouse=false;this.touch=null;this.blocked=false;this.engine.reset();}
  destroy(){this.reset();for(const remove of this.listeners)remove();this.listeners=[];}
}
if(typeof module!=='undefined'&&module.exports)module.exports=HiddenInput;
