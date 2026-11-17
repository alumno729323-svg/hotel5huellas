# language: es
Característica: Validar la reserva de una mascota en Hotel 5 Huellas
  Para evitar reservas imposibles
  como hotel
  quiero que el formulario rechace datos incorrectos

  Antecedentes:
    Dado que hoy es "2026-12-01"

  Escenario: Caso normal - reserva correcta
    Cuando reservo para "Ana" a "Toby" del "2026-12-10" al "2026-12-14"
    Entonces la reserva es aceptada
    Y la estancia es de 4 noches

  Escenario: Datos incorrectos - la salida es anterior a la entrada
    Cuando reservo para "Ana" a "Toby" del "2026-12-14" al "2026-12-10"
    Entonces la reserva es rechazada con el mensaje "La salida debe ser después de la entrada."

  Escenario: Datos incorrectos - falta el nombre de la mascota
    Cuando reservo para "Ana" a "" del "2026-12-10" al "2026-12-14"
    Entonces la reserva es rechazada con el mensaje "Indica el nombre de la mascota."

  Escenario: Caso límite - exactamente 30 noches es válido
    Cuando reservo para "Ana" a "Toby" del "2026-12-10" al "2027-01-09"
    Entonces la reserva es aceptada
    Y la estancia es de 30 noches

  Escenario: Caso límite - 31 noches se rechaza
    Cuando reservo para "Ana" a "Toby" del "2026-12-10" al "2027-01-10"
    Entonces la reserva es rechazada con el mensaje "Máximo 30 noches por reserva."

  Escenario: Caso límite - entrar hoy mismo es válido
    Cuando reservo para "Ana" a "Toby" del "2026-12-01" al "2026-12-02"
    Entonces la reserva es aceptada
