import logging
import time
import sys
from os import getpid

logging.basicConfig(filename='test-'+str(getpid())+'.log', level=logging.DEBUG, format='%(asctime)s %(levelname)-8s %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
#logging.basicConfig(filename='../writable/logs/sqlQueue-'+str(getpid())+'.log', level=logging.DEBUG)

logging.debug('This message should go to the log file')
#logging.info('So should this')
#logging.warning('And this, too')
#logging.error('And non-ASCII stuff, too, like Øresund and Malmö')


print("start receiving sql...\r\n")
