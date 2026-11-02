'use strict';
function hiddenMenuHeight(viewportBottom, hostTop, parentHeight, inBody) {
  // The host's actual top includes body margins, tile headings and wrapper offsets.
  // Never derive a larger surface from the parent than fits in the visible viewport.
  const viewportSpace=Math.max(0,viewportBottom-Math.max(0,hostTop)-16);
  // parentHeight is measured with a collapsed host; no space left means the parent
  // is sized by its content and only the viewport limits the surface.
  const parentSpace=parentHeight-32;
  return Math.floor(inBody || parentSpace<=0 ? viewportSpace : Math.min(viewportSpace,parentSpace));
}
if(typeof module!=='undefined'&&module.exports)module.exports=hiddenMenuHeight;
