import '@testing-library/jest-dom';

// Mock mobile viewport
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(), // deprecated
    removeListener: vi.fn(), // deprecated
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
});

// Mock touch events
global.Touch = class Touch {
  constructor(touchInit) {
    this.identifier = touchInit.identifier;
    this.target = touchInit.target;
    this.clientX = touchInit.clientX;
    this.clientY = touchInit.clientY;
    this.pageX = touchInit.pageX || touchInit.clientX;
    this.pageY = touchInit.pageY || touchInit.clientY;
  }
};

global.TouchEvent = class TouchEvent extends Event {
  constructor(type, eventInit = {}) {
    super(type, eventInit);
    this.touches = eventInit.touches || [];
    this.targetTouches = eventInit.targetTouches || [];
    this.changedTouches = eventInit.changedTouches || [];
  }
};

// Mock IntersectionObserver for lazy loading tests
global.IntersectionObserver = class IntersectionObserver {
  constructor(callback) {
    this.callback = callback;
  }

  observe() {
    return null;
  }

  disconnect() {
    return null;
  }

  unobserve() {
    return null;
  }
};

// Mock ResizeObserver
global.ResizeObserver = class ResizeObserver {
  constructor(callback) {
    this.callback = callback;
  }

  observe() {
    return null;
  }

  disconnect() {
    return null;
  }

  unobserve() {
    return null;
  }
};

// Helper functions for testing
global.createMockElement = (tagName = 'div', attributes = {}) => {
  const element = document.createElement(tagName);
  Object.keys(attributes).forEach(key => {
    element.setAttribute(key, attributes[key]);
  });
  return element;
};

global.simulateTouch = (element, eventType = 'touchstart', coordinates = { x: 0, y: 0 }) => {
  const touch = new Touch({
    identifier: 1,
    target: element,
    clientX: coordinates.x,
    clientY: coordinates.y,
  });

  const touchEvent = new TouchEvent(eventType, {
    touches: [touch],
    targetTouches: [touch],
    changedTouches: [touch],
  });

  element.dispatchEvent(touchEvent);
};

global.simulateSwipe = (element, direction = 'left', distance = 100) => {
  const startX = direction === 'left' ? distance : 0;
  const endX = direction === 'left' ? 0 : distance;

  simulateTouch(element, 'touchstart', { x: startX, y: 50 });
  simulateTouch(element, 'touchmove', { x: endX, y: 50 });
  simulateTouch(element, 'touchend', { x: endX, y: 50 });
};