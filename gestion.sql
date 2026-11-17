
CREATE DATABASE gestion_examenes;
USE gestion_examenes;

CREATE TABLE Roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Rol VARCHAR(45) NOT NULL);

CREATE TABLE Estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(45) NOT NULL UNIQUE,
    grado INT NOT NULL,
    grupo VARCHAR(2) NOT NULL
);

CREATE TABLE Profesor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    especialidad VARCHAR(45) NOT NULL
);


CREATE TABLE Usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(45) NOT NULL,
    Apellidos VARCHAR(45) NOT NULL,
    Correo VARCHAR(45) NOT NULL UNIQUE,
    `Contraseña` VARCHAR(255) NOT NULL,
    Roles_id INT NOT NULL,
    Estudiantes_id INT NULL UNIQUE,
    Profesor_id INT NULL UNIQUE,
    FOREIGN KEY (Roles_id)
        REFERENCES Roles(id),
    FOREIGN KEY (Estudiantes_id)
        REFERENCES Estudiantes(id),
    FOREIGN KEY (Profesor_id)
        REFERENCES Profesor(id)
);


CREATE TABLE Cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(45) NOT NULL,
    fecha_ini DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    Profesor_id INT NOT NULL,
    FOREIGN KEY (Profesor_id)
        REFERENCES Profesor(id)
);


CREATE TABLE Examenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_exam VARCHAR(45) NOT NULL,
    Fecha DATE NOT NULL,
    Cursos_id INT NOT NULL,
    FOREIGN KEY (Cursos_id)
        REFERENCES Cursos(id)
);


CREATE TABLE Preguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregunta VARCHAR(255) NOT NULL,
    tipo VARCHAR(45) NOT NULL,
    respuesta VARCHAR(255) NOT NULL,
    puntaje INT NOT NULL,
    Examenes_id INT NOT NULL,
    FOREIGN KEY (Examenes_id)
        REFERENCES Examenes(id)
);


CREATE TABLE Respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    respuesta VARCHAR(255) NOT NULL,
    fecha_envio DATETIME NOT NULL,
    Preguntas_id INT NOT NULL,
    FOREIGN KEY (Preguntas_id)
        REFERENCES Preguntas(id)
);


CREATE TABLE Calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calificaciones DECIMAL(5,2) NOT NULL,
    Fecha DATE NOT NULL,
    Examenes_id INT NOT NULL,
    FOREIGN KEY (Examenes_id)
        REFERENCES Examenes(id)
);


CREATE TABLE inscripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    Cursos_id INT NOT NULL,
    Estudiantes_id INT NOT NULL,
    FOREIGN KEY (Cursos_id)
        REFERENCES Cursos(id),
    FOREIGN KEY (Estudiantes_id)
        REFERENCES Estudiantes(id),
    UNIQUE (Cursos_id, Estudiantes_id)
);
