'use strict';
function hiddenMenuHeight(viewportBottom, hostTop, parentHeight, inBody) {
  // The host's actual top includes body margins, tile headings and wrapper offsets.
  // Never derive a larger surface from the parent than fits in the visible viewport.
  const viewportSpace=Math.max(0,viewportBottom-Math.max(0,hostTop)-16);
  return Math.floor(inBody ? viewportSpace : Math.min(viewportSpace,Math.max(0,parentHeight-32)));
}
if(typeof module!=='undefined'&&module.exports)module.exports=hiddenMenuHeight;
