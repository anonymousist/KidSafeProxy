#!/usr/bin/env python
# kidsafe child safe proxy server using squid
# see http://www.penguintutor.com/kidsafe
# kidsafe.py - squid v3 authethentication helper application
# Copyright Stewart Watkiss 2013

# This file is part of kidsafe.
# 
# kidsafe is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# kidsafe is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with kidsafe.  If not, see <http://www.gnu.org/licenses/>.


import sys, time, datetime, os

# These are global variables so can be accessed elsewhere
# These are not normally updated during running
# Only loglevel needs to be global, but others kept together at start of file

# Set loglevel to amount of logging required
# 0 = no logging, 1 = critical only, 2 = significant (warning), 3 = disable all logging of accepts / denies, 4 = log entries where log=yes is set on rule, 5 = include denies, 6 = include accept, 7 = include session tests, 8 = detailed session log, 9 = debug, 10 = full debug
# Normally this should be level 4 (or level 5 if want to log all blocked sites)
# above 6 not recommended for normal use
# 3 is same as 2 at the moment
#loglevel = 6
loglevel = 10
session_file = "/opt/kidsafe/kidsafe.session"
rules_file = "/opt/kidsafe/kidsafe.rules"
log_file = "/var/log/squid3/kidsafe.log"


def main ():

	global loglevel
	global rules_file
	global session_file

	# store time when files were last modified - if this is updated then we reload the file
	r_mtime = os.path.getmtime(rules_file)
	s_mtime = os.path.getmtime(session_file)

	if (loglevel >= 2):
		logEntry (2, "kidsafe starting")
	

	# Session list
	# holds whether user is logged in and at what level access they have
	sessions = loadSessions(session_file); 

	# List to hold whitelist
	# each entry in the whitelist is a list containing
	rules = loadRules(rules_file);
	

	while (1):
		# read entry from stdin - src, dst, dstport
		inline = sys.stdin.readline()
		# only use src & dst; dstport is for logging purposes
		# path is included, but not used. In future this can be used for additional keyword checks against url
		(src, dst, dstport, path) = inline.split()
		
		if (loglevel >= 10):
			logEntry (10, "New proxy request "+inline)
		# current time - we use this later to check for expired entries / sessions
		timenow = int(time.time());
	
		# convert dst to lowercase (rest should be IP addresses)
		dst = dst.lower()
		
		# check if sessions file updated (mtime) to see if we need to reload
		if (loglevel >= 10):
			logEntry (10, "last loaded mtime "+str(s_mtime))
			logEntry (10, "new mtime "+str(os.path.getmtime(session_file)))
		if (s_mtime != os.path.getmtime(session_file)):
			sessions = loadSessions(session_file)
			s_mtime = os.path.getmtime(session_file)

		# check if rules file updated (mtime) to see if we need to reload
		if (loglevel >= 10):
			logEntry (10, "last loaded mtime "+str(r_mtime))
			logEntry (10, "new mtime "+str(os.path.getmtime(rules_file)))
		if (r_mtime != os.path.getmtime(rules_file)):
			rules = loadRules(rules_file)
			r_mtime = os.path.getmtime(rules_file)

		# reset authorisation level
		authlevel = 0

		# check to see if the user is logged on and get permission level
		for sessionentry in sessions:
			# check time not expired (when we loaded we checked, but time could have expired since)
			if (sessionentry[3]!='*' and sessionentry[3]!= '0' and int(sessionentry[3]) < timenow):
				# may not be relevant, but we include in level 8
				if (loglevel >= 8):
					logEntry (8, "Expired session "+str(sessionentry))
				# expired to move to next
				continue
			if (checkAddress (src,sessionentry[0])):
				# Log all matches if on session log level
				if (loglevel >= 8):
					logEntry (8, "Session match "+str(sessionentry))
				# set auth level if higher
				if (sessionentry[1] > authlevel):
					# cast this as an int - otherwise int tests fail later
					authlevel = int(sessionentry[1])
			elif (loglevel >= 10):
				logEntry (10, "Session not matched "+src+" "+str(sessionentry))
		if (loglevel >= 7):
			logEntry (7, "Highest permission current session "+str(authlevel))

		# Special case - level 10 is accept all & no log to return as OK
		if (authlevel > 9):
			sys.stdout.write("OK\n")
			sys.stdout.flush()
			continue

		# Check against rules
		# have we had an accept?
		accept = False
		# rulematch will hold the rule number that get a hit on for logging  
		rulematch = 0
		# set logentry if status is to log error
		logentry = 1
		for ruleentry in rules:
			# check rule not expired (since generated)
			if (ruleentry[4]!='*' and ruleentry[4]!= '0' and int(ruleentry[4]) < timenow):
				# may not be relevant, but we include in level 9
				if (loglevel >= 9):
					logEntry (9, "Expired rule "+str(ruleentry))
					continue
			# check if the user level matches this rule
			if (checkUserLevel(authlevel, ruleentry[3]) == False) : 
				if (loglevel >= 10) :
					logEntry (10, "User level not matched on rule "+str(ruleentry[1]))
				continue
			if (loglevel >= 10) :
				logEntry (10, "User level match on rule "+str(ruleentry[1]))
				
			# check if the destination matches
			if checkDst(dst, ruleentry[0]) :
				rulematch = ruleentry[1]
				if (loglevel >= 10) :
					logEntry (10, "Destination match on rule "+str(rulematch))
				logentry = ruleentry[5]
				# is this an accept or a deny rule
				# allow if not 0
				if (int(ruleentry[2]) != 0) :
					if (loglevel >= 10) :
						logEntry (10, "Rule "+str(rulematch)+" is allow "+str(ruleentry[2]))
					accept = True
					break
				# deny
				else :
					if (loglevel >= 10) :
						logEntry (10, "Rule "+str(rulematch)+" is deny "+str(ruleentry[2]))
					accept = False
					break
			else :
				if (loglevel >= 9):
					logEntry (9, "Rule doesn't match destination")

		if (loglevel >= 10) :
			logEntry (10, "RULES checked accept = "+str(accept))
	
			

		# if accept has been changed to True - return OK otherwise return ERR
		# if logging because it's set in rule then use loglevel 4, otherwise 5 / 6 as appropriate
		if (accept == True) :
			if (loglevel >= 4 and logentry != '0'):
				logEntry (4, "ACCEPT "+src+" -> "+dst+":"+str(dstport)+" rule:"+str(rulematch))
			elif (loglevel >= 6):
				logEntry (6, "ACCEPT "+src+" -> "+dst+":"+str(dstport)+" rule:"+str(rulematch))
			sys.stdout.write("OK\n")
		else :
			if (loglevel >= 4 and logentry != '0'):
				logEntry (4, "REJECT "+src+" -> "+dst+":"+str(dstport)+" rule:"+str(rulematch))
			elif (loglevel >= 5):
				logEntry (5, "REJECT "+src+" -> "+dst+":"+str(dstport)+" rule:"+str(rulematch))
			sys.stdout.write("ERR\n")
		sys.stdout.flush()


# Open and close the file each time so that we don't run the risk of keeping the file 
# open when another thread wants to write to it. 
def logEntry(logmessagelevel, logmessage):

	global log_file
	# Get timestamp as human readable format
	now = datetime.datetime.now()
	timestamp = now.strftime("%Y-%m-%d %H:%M:%s")
	
	# open file to apptend
	logfile = open(log_file, 'a')
	logfile.write(timestamp+" "+str(logmessagelevel)+" "+logmessage+"\n")
	logfile.close()
	return
	


def loadRules(filename):
	global loglevel
	ruleslist = list()
	
	# Read in rules file
	ruleslistfile = open(filename, 'r')
	
	# currenttime
	timenow = int(time.time());
	
	# Use linecount to track position in file - in case of error
	# read in each line
	for linecount, entry in enumerate(ruleslistfile):
		entry = entry.rstrip()
		# ignore any empty lines / comments
		if (entry and not(entry.startswith('#'))):
			thisLine = entry.split(' ')
			# check there is a valid entry (basic check of number of elements in entry)
			if (len(thisLine) < 6):
				if (loglevel >= 1):
					logEntry(1, "Invalid entry in rules file line %d \n" % (linecount))
				# Print message and abort
				#print ("Invalid entry in rules file line %d \n" % (linecount))
				# print deny
				print "ERR\n"
				sys.exit()
			# check not expired
			if (thisLine[4]!='*' and thisLine[4]!= '0' and int(thisLine[4]) < timenow):
				if (loglevel >= 9):
					logEntry (9, "Expired rule (load) "+str(entry))
					continue
				# if expired move on to next entry (ignore)
				continue
			ruleslist.append (thisLine)
	ruleslistfile.close()
	
	if (loglevel >= 2):
		logEntry(2, "loaded rules file")
	# debug level >=9 is not recommended for normal use
	if (loglevel >= 9):
		all_entries = "";
		for each_entry in ruleslist:
			all_entries += str(each_entry)+"\n"
		logEntry (9, "Rules entries:\n"+all_entries)
	return ruleslist


# returns current login level for this IP address (highest value)
def loadSessions(filename):
	global loglevel
	sessionlist = list()
	
	# Read in whitelist file
	sessionlistfile = open(filename, 'r')
	
	# currenttime
	timenow = int(time.time());
	
	# Use linecount to track position in file - in case of error
	# read in each line
	for linecount, entry in enumerate(sessionlistfile):
		entry = entry.rstrip()
		# ignore any empty lines / comments
		if (entry and not(entry.startswith('#'))):
			thisLine = entry.split(' ')
			# check there is a valid entry (basic check of number of elements in entry)
			if (len(thisLine) < 4):
				if (loglevel >=1 ):
					logEntry (1, "Invalid entry in session file line %d \n" %(linecount))
				# Print message and abort
				#print ("Invalid entry in sessions file line %d \n" % (linecount))
				print "ERR\n"
				sys.exit()
			# check not expired
			if (thisLine[3]!='*' and thisLine[3]!= '0' and int(thisLine[3]) < timenow):
				# if expired move on to next entry (ignore) - only skip here for efficiency later as we need to check this in case it changes in future anyway
				# may not be relevant, but we include in level 9 (ie higher than normal session log level)
				if (loglevel >= 9):
					logEntry (9, "Expired session (load) "+str(entry))
					continue
			sessionlist.append (thisLine)
	sessionlistfile.close()
	
	if (loglevel >= 2):
		logEntry(2, "loaded session file")
	# debug level >=9 is not recommended for normal use
	if (loglevel >= 9):
		all_entries = "";
		for each_entry in sessionlist:
			all_entries += str(each_entry)+"\n"
		logEntry (9, "Session entries:\n"+all_entries)
	return sessionlist





# function to check if a specific destination matches a particular rule
# rule should just be the domain/host part of the whitelist
def checkDst(dest, rule):
	# check for * rule (normally used to always allow for a specific source IP address or to temporarily disable
	if (rule=='*'):
		return True
	# check specific rule first - more efficient than rest
	if (dest==rule): 
		return True
	# does entry start with a . (if so then check using endswith)
	if (rule.startswith('.')) :
		if (dest.endswith(rule)):
			return True
		else :
			return False
	# least efficient - regular expression
	elif (dest.startswith('/')) :
			if re.match (rule, dest) :
				return True
			else :
				return False
	# No match
	else :
		return False



# check if our IP address matches that in the rule
# currently accept fixed IP address or regexp (add subnets in future)
def checkAddress(src, session):
	# First try a normal ip address (most likely match)
	if (src == session):
		return True
	# look for a regular expression
	elif session.startswith ('/'):
		if re.match (session, src) : 
			return True
		else : 
			return False;
	# if it's a subnet (not yet implemented)
	#elif session.find('/')
	# otherwise it's a normal IP address
	else:
		return False
		
# check to see if user level matches (supports +/- after the value)
def checkUserLevel(authlevel, ruleuser):
	# rule = * applies to all users
	if (ruleuser=='*') : return True
	# split into comma separated entries if applicable 
	listruleuser = ruleuser.split (',')
	for thisruleuser in listruleuser:
		# get int representation (with +/- removed)
		ruleuserint = int (thisruleuser.rstrip('+-'))
		# first check for exact match (don't need to know if it had +/-
		if (authlevel == ruleuserint) : return True
		# check with +
		if (ruleuser.endswith('+')) :
			if (authlevel > ruleuserint) : return True
		elif (ruleuser.endswith('-')) :
			if (authlevel < ruleuserint) : return True
	# if not matched
	return False


# - Added inline instead as more efficient than function call
## function to check if a particular rule has expired
## uses unix time stamp - or * for no expiry
#def checkExpire(expiretime):
#	if (expiretime == '*' or expiretime == '0'): return True
#	timenow = int(time.time());
#	if (int(expiretime) > timenow): return True;
#	return False


# Start
if __name__ == '__main__':
	main()

