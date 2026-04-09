import { hasOpenModals } from "./modalStack";
let prevBodyPadding = null;
let prevHtmlPadding = null;
const LOCK_CLASS = "modal-scroll-locked";

function ensureLockStyle() {
    if (document.getElementById("modal-scroll-lock-style")) return;
    const style = document.createElement("style");
    style.id = "modal-scroll-lock-style";
    style.innerHTML = `.${LOCK_CLASS} { overflow: hidden !important; }`;
    document.head.appendChild(style);
}

export function lockScrollIfNeeded() {
    ensureLockStyle();

    // If already locked, do nothing
    if (
        document.documentElement.classList.contains(LOCK_CLASS) ||
        document.body.classList.contains(LOCK_CLASS)
    ) {
        return;
    }

    // NOTE: We intentionally do NOT add padding-right here. Previously we added
    // scrollbar width as padding to prevent layout shift when hiding the
    // scrollbar. The user requested that when the scrollbar disappears the
    // content should occupy the full width, so we avoid injecting extra
    // padding. We still keep prevBodyPadding/prevHtmlPadding in case other
    // code set inline paddings earlier and they need to be restored later.
    if (prevBodyPadding === null)
        prevBodyPadding = document.body.style.paddingRight;
    if (prevHtmlPadding === null)
        prevHtmlPadding = document.documentElement.style.paddingRight;

    document.documentElement.classList.add(LOCK_CLASS);
    document.body.classList.add(LOCK_CLASS);
}

export function unlockScrollIfNeeded() {
    // Only unlock if there are no open modals
    if (hasOpenModals()) return;

    document.documentElement.classList.remove(LOCK_CLASS);
    document.body.classList.remove(LOCK_CLASS);

    if (prevBodyPadding !== null) {
        document.body.style.paddingRight = prevBodyPadding;
        prevBodyPadding = null;
    }
    if (prevHtmlPadding !== null) {
        document.documentElement.style.paddingRight = prevHtmlPadding;
        prevHtmlPadding = null;
    }
}
