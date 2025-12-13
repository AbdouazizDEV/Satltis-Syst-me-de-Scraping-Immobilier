# 🔧 Guide de dépannage Railway

## Erreur : "Application failed to respond"

### Causes possibles

1. **Variables d'environnement non définies** (le plus probable)
2. **Apache ne démarre pas correctement**
3. **Port mal configuré**
4. **Application crash au démarrage**

### Solution 1 : Vérifier les variables d'environnement

**C'est la cause la plus fréquente !**

1. Allez dans Railway → Votre service → **Variables**
2. Vérifiez que ces variables sont présentes :
   ```
   APP_ENV=production
   APP_DEBUG=false
   DB_CONNECTION=pgsql
   DB_URL=postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require
   ```

3. Si elles ne sont pas présentes, ajoutez-les (voir `RAILWAY_ENV_VARIABLES.md`)

### Solution 2 : Vérifier les logs Railway

1. Allez dans Railway → Votre service → **Logs**
2. Cherchez ces messages :

**✅ Si vous voyez** :
```
=== Configuration PostgreSQL détectée via DB_URL ===
=== Démarrage d'Apache ===
Apache va démarrer et écouter sur le port XXXX
```

**❌ Si vous voyez** :
```
⚠️ ATTENTION: Aucune configuration PostgreSQL détectée !
DB_URL: non défini
```

→ Les variables d'environnement ne sont pas définies. Ajoutez-les dans l'onglet Variables.

### Solution 3 : Vérifier que Apache démarre

Dans les logs, cherchez :
```
=== Démarrage d'Apache ===
Apache va démarrer et écouter sur le port XXXX
```

Si vous ne voyez pas ce message, Apache n'a peut-être pas démarré.

### Solution 4 : Vérifier le port

Railway utilise une variable `PORT` dynamique. Le script devrait automatiquement configurer Apache pour utiliser ce port.

Dans les logs, vous devriez voir :
```
=== Configuration du port Apache ===
Port Railway (PORT): XXXX
Port Apache: XXXX
```

## Vérification étape par étape

### 1. Vérifier le déploiement

- Allez dans **Deployments**
- Vérifiez que le dernier déploiement est **"ACTIVE"** avec un ✅ vert
- Si le déploiement a échoué, cliquez dessus pour voir les logs de build

### 2. Vérifier les variables d'environnement

- Allez dans **Variables**
- Vérifiez que `DB_URL` ou `DB_HOST` est défini
- Vérifiez que `APP_ENV=production`

### 3. Vérifier les logs en temps réel

- Allez dans **Logs**
- Regardez les dernières lignes
- Cherchez les erreurs en rouge

### 4. Redéployer

Si rien ne fonctionne :
1. Allez dans **Deployments**
2. Cliquez sur "Redeploy" ou "Deploy"
3. Attendez la fin du déploiement
4. Vérifiez les logs

## Messages d'erreur courants

### "DB_URL: non défini"

**Solution** : Ajoutez `DB_URL` dans l'onglet Variables de Railway

### "AH00534: apache2: Configuration error: More than one MPM loaded"

**Solution** : Cette erreur devrait être corrigée dans la dernière version. Redéployez.

### "Application failed to respond"

**Causes possibles** :
1. Variables d'environnement manquantes
2. Apache ne démarre pas
3. Port mal configuré

**Solution** :
1. Vérifiez les variables d'environnement
2. Vérifiez les logs pour voir si Apache démarre
3. Redéployez si nécessaire

## Commandes utiles pour déboguer

Si vous avez accès au shell Railway :

```bash
# Vérifier les variables d'environnement
env | grep DB_
env | grep APP_ENV

# Vérifier la configuration Apache
apache2ctl configtest

# Vérifier que Apache écoute sur le bon port
netstat -tlnp | grep apache

# Vérifier les logs Apache
tail -f /var/log/apache2/error.log
```

## Support

Si le problème persiste :
1. Vérifiez les logs complets dans Railway
2. Vérifiez que toutes les variables d'environnement sont définies
3. Vérifiez que le déploiement est réussi
4. Contactez le support Railway si nécessaire

