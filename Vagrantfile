# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # this vagrant machine exists to test the shell config provisioning, the db vm is already loaded
  # with demo data and is ready to work with
  # database server
  config.vm.define "db2" do |db|
    db.vm.hostname = "db.mcfc.local"
    db.vm.box = "precise32"
    db.vm.box_url = "http://files.vagrantup.com/precise32.box"
    db.vm.network :private_network, ip: "33.33.33.10"

    # install/checkout/configuration
    config.vm.provision :shell do |sh|
      sh.inline = <<-EOF
        apt-get update
        #apt-get upgrade
        # setting mysql root user password
	echo "mysql-server mysql-server/root_password select root" | debconf-set-selections
	echo "mysql-server mysql-server/root_password_again select root" | debconf-set-selections
        # debugging and programming
        apt-get install -y git tmux mysql-client
        # standard requirements
        apt-get install -y mysql-server apache2 php5 libapache2-mod-php5 python-mysqldb php5-mysql php-fpdf
        # no php-image-barcode
        #apt-get install -y mysql-server apache2 php5 libapache2-mod-php5 python-mysqldb php5-mysql php-image-barcode php-fpdf
	mkdir -p /home/vagrant/src
        # eventually move to a tarball or something like that
        git clone https://github.com/Missoula-Food-Coop/IS4C.git /home/vagrant/src/IS4C
        chown -R vagrant:vagrant /home/vagrant/src
        ln -s /home/vagrant/src/IS4C/pos/ /pos
        #/pos/installation/ubuntu/install-server.sh

      # here's where server setup and db population scripts go, once they're written...
      # once the sample data isn't screwed up this will work, though would be better
      # in a shell script...
        mysql --user=root --password=root < /pos/installation/mysql/script/create_server_db.sql
        for FN in $(ls /pos/installation/mysql/is4c_log/tables/*.table)
        do
          echo "Inserting records from $FN"
          mysql --user=root --password=root < $FN
        done
        for FN in $(ls /pos/installation/mysql/is4c_log/views/*.viw)
        do
          echo "Inserting records from $FN"
          mysql --user=root --password=root < $FN
        done
        for FN in $(ls /pos/installation/mysql/is4c_op/tables/*.table)
        do
          echo "Inserting records from $FN"
          mysql --user=root --password=root < $FN
        done
        for FN in $(ls /pos/installation/mysql/is4c_op/views/*.viw)
        do
          echo "Inserting records from $FN"
          mysql --user=root --password=root < $FN
        done
        for FN in $(ls /pos/installation/mysql/is4c_op/data/*.insert)
        do
          echo "Inserting records from $FN"
          mysql --user=root --password=root < $FN
        done
        mysql --user=root --password=root < /pos/installation/mysql/script/create_server_acct.sql
        # we can do better by just copying the template files
        #/pos/installation/ubuntu/php_server.pl
        #/pos/installation/ubuntu/apache_server.pl
      EOF

    end

    config.vm.provider "virtualbox" do |v|
      # turn on the gui for funsies
      #v.gui = true
      v.name = "db.mcfc.dev"
    end
  end
  config.vm.define "db" do |db|
    db.vm.hostname = "db.mcfc.local"
    db.vm.box = "precise32"
    db.vm.box_url = "http://files.vagrantup.com/precise32.box"
    db.vm.network :private_network, ip: "33.33.33.10"

    # shell configuration
    # first set of installs are for dev work and debugging
    config.vm.provision :shell do |sh|
      sh.inline = <<-EOF
        sudo apt-get update
        sudo apt-get upgrade
        sudo apt-get install -y git tmux mysql-client
        sudo apt-get install -y mysql-server apache2 php5 libapache2-mod-php5 python-mysqldb php5-mysql php-image-barcode php-fpdf
        mkdir ~/src
        cd ~/src
        git clone https://github.com/Missoula-Food-Coop/IS4C.git
        sudo ln -s /home/vagrant/src/IS4C/pos/ /pos
      EOF
      # here's where server setup and db population scripts go, once they're written...
    end
  end

  # lane one
  config.vm.define "lane_1" do |lane_1|
    lane_1.vm.hostname = "lane-1.mcfc.local"
    lane_1.vm.box = "precise32"
    lane_1.vm.box_url = "http://files.vagrantup.com/precise32.box"
    lane_1.vm.network :private_network, ip: "33.33.33.11"

    # shell configuration
    config.vm.provision :shell do |sh|
      sh.inline = <<-EOF
        sudo apt-get update
        sudo apt-get upgrade
        sudo apt-get install git
        mkdir ~/src
        cd ~/src
        git clone https://github.com/Missoula-Food-Coop/IS4C.git
        sudo ln -s /home/vagrant/src/IS4C/pos/ /pos
      EOF
    end
  end

  # lane two
  config.vm.define "lane_2" do |lane_2|
    lane_2.vm.hostname = "lane-2.mcfc.local"
    lane_2.vm.box = "precise32"
    lane_2.vm.box_url = "http://files.vagrantup.com/precise32.box"
    lane_2.vm.network :private_network, ip: "33.33.33.12"

    # shell configuration
    config.vm.provision :shell do |sh|
      sh.inline = <<-EOF
        sudo apt-get update
        sudo apt-get upgrade
        sudo apt-get install git
        mkdir ~/src
        cd ~/src
        git clone https://github.com/Missoula-Food-Coop/IS4C.git
        sudo ln -s /home/vagrant/src/IS4C/pos/ /pos
      EOF
    end
  end
end
