@php
    $active = $active ?? false;
    $svgClass = $class ?? null;
@endphp
<svg @class($svgClass ?: ['h-6 w-6', $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive']) width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.25 20.25V9.75L12 5.25L19.75 9.75V20.25H4.25ZM5.75 18.75H18.25V10.5L12 7.125L5.75 10.5V18.75ZM9.75 18.75V13.5H14.25V18.75H9.75ZM6.75 9.75H17.25V8.25L12 5.25L6.75 8.25V9.75Z" fill="currentColor"/>
</svg>
