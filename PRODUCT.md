# Wallet App

## Platform

web

## Users

Persona que administra sus finanzas personales y necesita distinguir fondos de ahorro y uso diario sin abrir una cuenta bancaria por cada propósito.

## Product Purpose

Organizar dinero mediante bolsillos virtuales independientes. El dinero puede estar físicamente en una cuenta bancaria mientras se distribuye lógicamente entre bolsillos. La experiencia debe facilitar entender cuánto hay disponible y qué movimientos explican cada saldo.

## Capabilities and Constraints

- La web actual permite registro, inicio/cierre de sesión y gestión de bolsillos con saldos, creación, edición, desactivación y reactivación.
- El backend también implementa movimientos, balances, transferencias, reportes y pagos recurrentes. Su existencia en la API no implica que tengan pantallas web terminadas.
- Los balances proceden del libro de movimientos; la interfaz no recalcula reglas financieras. Se conserva el historial y la desactivación lógica.
- Web con cookies, sesión y CSRF. Conservar las dependencias y componentes existentes cuando resuelvan la necesidad.
- La interfaz actual formatea importes en español de Guatemala y GTQ. No inventar compatibilidad bancaria, sincronización, clientes, métricas ni funcionalidades móviles.

## Brand Commitments

Nombre: Wallet App. Tono sobrio, moderno, limpio, financiero, claro y profesional. Respetar los patrones visuales aprobados; no sustituirlos sin una solicitud de rediseño. Evitar decoración y efectos que dificulten leer importes o comprender acciones.

## Product Principles

1. Priorizar claridad sobre ornamentación: importes, propósito de cada bolsillo y acciones inequívocas.
2. Preservar confianza e historial financiero; explicar consecuencias y prevenir errores en acciones sensibles.
3. Mantener navegación y componentes consistentes, con feedback de carga, éxito, error y estados vacíos.
4. Diseñar para distintos tamaños de pantalla sin perder jerarquía ni legibilidad.

## Accessibility & Inclusion

Prioridades explícitas: claridad, accesibilidad, jerarquía, consistencia, responsive y estética. Mantener contraste legible, etiquetas comprensibles, uso por teclado y foco visible. No depender únicamente del color para comunicar estados.
