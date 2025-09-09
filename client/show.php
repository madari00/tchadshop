	#	Nom	Type	Interclassement	Attributs	Null	Valeur par défaut	Commentaires	Extra	Action
	1	id Primaire	int			Non	Aucun(e)		AUTO_INCREMENT	Modifier Modifier	Supprimer Supprimer	
	2	nom	varchar(255)	utf8mb4_0900_ai_ci		Oui	NULL			Modifier Modifier	Supprimer Supprimer	
	3	description	text	utf8mb4_0900_ai_ci		Oui	NULL			Modifier Modifier	Supprimer Supprimer	
	4	prix	decimal(10,2)			Oui	NULL			Modifier Modifier	Supprimer Supprimer	
	5	stock	int			Oui	NULL			Modifier Modifier	Supprimer Supprimer	
	6	statut	enum('disponible', 'rupture', 'bientôt')	utf8mb4_0900_ai_ci		Oui	disponible			Modifier Modifier	Supprimer Supprimer	
	7	created_at	timestamp			Oui	CURRENT_TIMESTAMP		DEFAULT_GENERATED	Modifier Modifier	Supprimer Supprimer	
	8	vu	tinyint(1)			Oui	0			Modifier Modifier	Supprimer Supprimer	
