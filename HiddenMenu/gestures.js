'use strict';
class HiddenGesture {
  constructor(config, opened, clock = globalThis) {
    this.config = config; this.opened = opened; this.clock = clock; this.reset();
  }
  reset() {
    this.clock.clearTimeout(this.tapTimer); this.clock.clearTimeout(this.stepTimer);
    this.taps = 0; this.step = 0; this.pointer = null;
  }
  armTimeout() {
    this.clock.clearTimeout(this.stepTimer);
    this.stepTimer = this.clock.setTimeout(() => this.reset(), this.config.stepTimeout);
  }
  emit(gesture) {
    this.clock.clearTimeout(this.stepTimer);
    if (this.config.gesture[this.step] !== gesture) { this.reset(); return; }
    this.step++;
    if (this.step === this.config.gesture.length) { this.reset(); this.opened(); }
    else this.armTimeout();
  }
  finishTaps() {
    this.clock.clearTimeout(this.tapTimer);
    const n = this.taps; this.taps = 0;
    if (n) this.emit('tap' + n);
  }
  down(id, x, y, time) {
    if (this.pointer) { this.reset(); return false; }
    // Once a new contact begins, its duration must not consume the inter-step pause.
    this.clock.clearTimeout(this.stepTimer);
    this.clock.clearTimeout(this.tapTimer);
    this.pointer = {id, x, y, time, max: 0};
    return true;
  }
  move(id, x, y) {
    if (!this.pointer || this.pointer.id !== id) return;
    this.pointer.max = Math.max(this.pointer.max, Math.hypot(x-this.pointer.x, y-this.pointer.y));
  }
  up(id, x, y, time) {
    if (!this.pointer || this.pointer.id !== id) return;
    this.move(id, x, y);
    const p = this.pointer; this.pointer = null;
    const dx = x-p.x, dy = y-p.y, distance = Math.hypot(dx, dy), duration = time-p.time;
    if (distance >= this.config.swipeDistance && duration <= 1800 && distance >= p.max * 0.75) {
      if (this.taps) this.finishTaps();
      const dirs = ['right','downRight','down','downLeft','left','upLeft','up','upRight'];
      this.emit(dirs[(Math.round(Math.atan2(dy,dx)/(Math.PI/4))+8)%8]);
    } else if (p.max <= 14 && duration >= this.config.holdTime) {
      if (this.taps) this.finishTaps();
      this.emit('hold');
    } else if (p.max <= 14 && duration <= Math.min(450, this.config.holdTime-1)) {
      this.taps++;
      if (this.taps > 5) { this.reset(); return; }
      // Only one configured sequence exists: its expected tap count is unambiguous.
      // Complete it immediately instead of waiting for another (unconfigured) count.
      if ('tap' + this.taps === this.config.gesture[this.step]) this.finishTaps();
      else this.tapTimer = this.clock.setTimeout(() => this.finishTaps(), this.config.tapGap);
    } else this.reset();
  }
}
if (typeof module !== 'undefined' && module.exports) module.exports = HiddenGesture;
