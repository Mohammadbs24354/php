create database umfrage;

use umfrage;

create table frage(
  id int not null auto_increment primary key,
  fragentext varchar(255) not null
);

create table moeglicheantwort(
  id int not null auto_increment primary key,
  frageid int not null,
  antworttext varchar(255) not null,
  foreign key(frageid) references frage(id)
);

create table nutzertoken(
  id int not null auto_increment primary key
);

create table abgegebeneantwort(
  id int not null auto_increment primary key,
  nutzertokenid int not null,
  frageid int not null,
  antwortid int not null,
  foreign key(nutzertokenid) references nutzertoken(id),
  foreign key(frageid) references frage(id),
  foreign key(antwortid) references moeglicheantwort(id)
);