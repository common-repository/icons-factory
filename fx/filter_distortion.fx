<filter id="distortion"> <feTurbulence id="turbulence" baseFrequency="0.02" numOctaves="3" result="noise" seed="0"/> <feDisplacementMap id="displacement" in="SourceGraphic" in2="noise" scale="6" /> </filter>