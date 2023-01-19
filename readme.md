# Virtualización

Esta implementación pretende virtualizar el presente proyecto, proveyéndonos de dos diferente opciones para tal fin.  (Vagrant y Docker)

Es decisión del programador determinar con cual herramienta desea virtualizar.

¿Cómo empezar?
- Descarga e Instala Make en tu sistema operativo.
    -	 [Linux](https://linuxhint.com/install-make-ubuntu/ "Linux")
    - [Windows](https://stackoverflow.com/questions/32127524/how-to-install-and-use-make-in-windows "Windows")
    - [MacOS](https://stackoverflow.com/questions/10265742/how-to-install-make-and-gcc-on-a-mac "MacOS")

**A continuación descarga e Instala Docker o Vagrant en tu sistema operativo -El de tu preferencia- (Ambas herramientas de virtualización son soportadas por esta implementación)**

# ------------------------------------------
### SOLO PARA USUARIOS MAC procura seguir las siguientes instrucciones:
- Verifica si el Cgroup de tu Docker es true:


    % cat ~/Library/Group\ Containers/group.com.docker/settings.json|grep deprecatedCgroupv1
      "deprecatedCgroupv1": false,
    
    # false --> true (Reemplazar)
    % vi ~/Library/Group\ Containers/group.com.docker/settings.json
    % cat ~/Library/Group\ Containers/group.com.docker/settings.json|grep deprecatedCgroupv1
      "deprecatedCgroupv1": true,- 

### Guardar el archivo, y reiniciar docker.
# ------------------------------------------

Comandos para realizar la virtualización:

Al mismo nivel que el makefile, ejecutar respectivamente:
- `make docker.start USERNAME="your_username" PASS="your_pass" PHP_VERSION="your_php_version"`

- `make vagrant.start USERNAME="your_username" PASS="your_pass" DATABASE_NAME="your_database_name" PHP_VERSION="your_php_version"`

Ejemplo de parametrización:
- USERNAME="bitsfulanito"
- PASS="mysupersecretpass123"
- PHP_VERSION="php7.4"`**(Por favor procure manejar esta sintaxis para la version de php)**

El proceso de virtualización termina cuando la terminal lo posicione dentro del contenedor.

- **make docker.up**  (Levanta los conetedores del proyecto)
- **make docker.end** (Detiene los contenedores del proyecto)
- **make docker.restart** (Reinicia contenedores)
- **make docker.it.project** (Ingresa por terminal al contenedor del proyecto)
- **make docker.it.bd**  (Ingresa por terminal al contenedor de la Base de datos)
- **make docker.destroy**  (Destruye el ambiente destruido, debes volver a ejecutar **make docker.start**)

